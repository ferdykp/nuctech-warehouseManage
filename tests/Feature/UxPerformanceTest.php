<?php

namespace Tests\Feature;

use App\Http\Middleware\PreserveListContext;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Reimbursement;
use App\Models\Salary;
use App\Models\Site;
use App\Models\User;
use App\Services\PayrollCalculation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UxPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_context_keeps_filters_but_never_overrides_the_server_destination(): void
    {
        $middleware = new PreserveListContext();
        $user = User::factory()->create(['role' => 'superadmin']);
        $request = Request::create(url('/salary/1'), 'POST', ['_ui_return' => '/salary?search=Engineer&page=2&month=09&token=secret']);
        $request->setUserResolver(fn () => $user);
        $response = $middleware->handle($request, fn () => redirect('/salary?month=10'));
        parse_str(parse_url($response->getTargetUrl(), PHP_URL_QUERY), $query);
        $this->assertSame(['search' => 'Engineer', 'page' => '2', 'month' => '10'], $query);
        $reportRequest = Request::create(url('/report/1'), 'POST', ['_ui_return' => '/report/qa-site?search=Engineer&page=2']);
        $reportRequest->setUserResolver(fn () => $user);
        $reportResponse = $middleware->handle($reportRequest, fn () => redirect('/report'));
        $this->assertSame(url('/report/qa-site').'?search=Engineer&page=2', $reportResponse->getTargetUrl());
        foreach (['//outside.test/salary', '/employee?search=other', '/salary?redirect=https://outside.test', '/salary?search[]=bad'] as $context) {
            $request->merge(['_ui_return' => $context]);
            $response = $middleware->handle($request, fn () => redirect('/salary'));
            $this->assertSame(url('/salary'), $response->getTargetUrl());
        }
    }

    public function test_batch_payroll_matches_individual_results_and_has_constant_query_count(): void
    {
        Http::fake();
        Cache::put('id_holidays_2026', ['2026-09-01' => 'Test holiday']);
        $branch = Branch::create(['branch_name' => 'Test', 'branch_code' => 'UX']);
        $site = Site::create(['branch_id' => $branch->id, 'machine_name' => 'Test Site', 'slug' => 'ux-site', 'location' => 'Test']);
        $salaries = collect();
        for ($i = 0; $i < 12; $i++) {
            $employee = Employee::create(['name' => 'Employee '.$i, 'site_id' => $site->id, 'branch_id' => $branch->id, 'phone_number' => '123',
                'mcu' => 'no', 'tld' => 'no', 'nik' => str_pad((string) $i, 16, '0', STR_PAD_LEFT), 'join_date' => '2026-01-01', 'basic_salary' => 5000000]);
            $salaries->push(Salary::create(['employee_id' => $employee->id, 'name' => $employee->name, 'position' => 'Engineer',
                'placement' => 'Test', 'bank' => 'BCA', 'account_no' => '123', 'amount' => 5000000 + $i, 'information' => 'regular salary']));
            Attendance::create(['employee_id' => $employee->id, 'month' => '2026-09', 'working_days' => 22,
                'attendance_count' => 2, 'matrix_details' => json_encode([1 => ['s1' => 1], 2 => ['s2' => 1]])]);
        }
        $service = app(PayrollCalculation::class);
        DB::enableQueryLog(); DB::flushQueryLog();
        $batch = $service->calculateMany($salaries, '2026-09');
        $queries = count(DB::getQueryLog()); DB::disableQueryLog();
        $this->assertLessThanOrEqual(2, $queries);
        foreach ($salaries as $salary) {
            $this->assertSame($service->calculate($salary->employee_id, '2026-09', (float) $salary->amount), $batch[$salary->id]);
        }
    }

    public function test_dashboard_tasks_and_claim_status_filter_respect_the_existing_scope(): void
    {
        Http::fake();
        $mine = User::factory()->create(['role' => 'employee_role']);
        $other = User::factory()->create(['role' => 'employee_role']);
        foreach ([[$mine, 'pending'], [$mine, 'approved'], [$other, 'pending']] as [$owner, $status]) {
            Reimbursement::create(['user_id' => $owner->id, 'person_name' => $owner->username, 'date' => '2026-09-01',
                'category' => 'office', 'amount' => 100, 'receipt_attachment' => 'test.png', 'status' => $status]);
        }
        $this->actingAs($mine)->get('/dashboard')->assertOk()->assertViewHas('tasks', fn ($tasks) => $tasks[0]['count'] === 1);
        $this->get('/reimbursements?status=pending')->assertOk()
            ->assertViewHas('reimbursements', fn ($rows) => $rows->total() === 1);
        $this->get('/reimbursements?status=approved')->assertOk()
            ->assertViewHas('reimbursements', fn ($rows) => $rows->total() === 1);
    }
    public function test_primary_workspaces_render_for_each_authorized_role(): void
    {
        Http::fake();
        $branch = Branch::create(['branch_name' => 'Role QA', 'branch_code' => 'ROLE']);
        $site = Site::create(['branch_id' => $branch->id, 'machine_name' => 'QA Site', 'slug' => 'qa-roles', 'location' => 'Test']);
        foreach (['superadmin', 'administration', 'team_leader', 'employee_role', 'station_master', 'manager'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role, 'site_id' => $site->id]));
            $paths = ['/dashboard', '/reimbursements', '/reimbursements/create'];
            if (in_array($role, ['superadmin', 'administration', 'team_leader', 'employee_role'])) {
                $paths = array_merge($paths, ['/schedules', '/attendance', '/leave', '/daily-reports']);
            }
            if (in_array($role, ['superadmin', 'team_leader'])) {
                $paths = array_merge($paths, ['/spareparts/all', '/inventory/qa-roles', '/site', '/report']);
            }
            if (in_array($role, ['superadmin', 'administration'])) $paths = array_merge($paths, ['/employee', '/salary']);
            foreach ($paths as $path) $this->followingRedirects()->get($path)->assertOk();
        }
    }

    public function test_excel_claim_filter_matches_the_filtered_list(): void
    {
        Http::fake();
        $user = User::factory()->create(['role' => 'superadmin']);
        $this->actingAs($user);
        foreach (['pending', 'approved', 'rejected'] as $status) {
            Reimbursement::create(['user_id' => $user->id, 'person_name' => 'QA', 'date' => '2026-09-01',
                'category' => 'office', 'amount' => 100, 'receipt_attachment' => 'test.png', 'status' => $status]);
        }
        $rows = (new \App\Exports\ReimbursementExport('QA', '09', true, 'waiting'))->collection();
        $this->assertCount(1, $rows);
        $this->assertSame('pending', $rows->first()->status);
    }

    public function test_report_search_returns_rows_and_pagination_and_bulk_delete_keeps_permissions(): void
    {
        Http::fake();
        \Illuminate\Support\Facades\Queue::fake();
        $branch = Branch::create(['branch_name' => 'Report QA', 'branch_code' => 'REPORT']);
        $site = Site::create(['branch_id' => $branch->id, 'machine_name' => 'QA', 'slug' => 'report-qa', 'location' => 'Test']);
        $report = \App\Models\Report::create(['site_id' => $site->id, 'site_machine' => $site->machine_name,
            'attendant' => 'Petugas Uji', 'failure_date' => '2026-10-19', 'failure_note' => 'Catatan uji', 'ts_procedure' => 'Prosedur uji']);
        $this->actingAs(User::factory()->create(['role' => 'team_leader', 'site_id' => $site->id]));
        $this->delete('/report/bulk', ['ids' => [$report->id]])->assertForbidden();
        $this->assertDatabaseHas('reports', ['id' => $report->id]);
        $this->actingAs(User::factory()->create(['role' => 'superadmin']));
        $this->getJson('/report/report-qa?search=Petugas', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonPath('total', 1)->assertJsonStructure(['html', 'pagination']);
        $this->delete('/report/bulk', ['ids' => [$report->id, 99999]])->assertSessionHasErrors('ids.1');
        $this->assertDatabaseHas('reports', ['id' => $report->id]);
        $this->delete('/report/bulk', ['ids' => [$report->id]])->assertRedirect(route('report.index'));
        $this->assertDatabaseMissing('reports', ['id' => $report->id]);
    }

    public function test_editing_a_claim_preserves_canonical_decimals_and_accepts_legacy_grouping(): void
    {
        $user = User::factory()->create(['role' => 'employee_role']);
        $this->actingAs($user);
        $claim = Reimbursement::create(['user_id' => $user->id, 'person_name' => 'QA', 'date' => '2026-10-19',
            'category' => 'office', 'amount' => 100000, 'receipt_attachment' => 'test.png', 'status' => 'pending']);
        foreach (['100000.00' => '100000.00', '100.000' => '100000.00', '100.000,50' => '100000.50', '12.34' => '12.34', '0.00' => '0.00'] as $input => $expected) {
            $this->put('/reimbursements/'.$claim->id, ['person_name' => 'QA', 'date' => '2026-10-19', 'category' => 'office',
                'amount' => $input, 'comment' => 'Catatan diperbarui'])
                ->assertRedirect(route('reimbursements.index'))->assertSessionHasNoErrors();
            $this->assertSame($expected, $claim->fresh()->amount);
        }
    }

}
