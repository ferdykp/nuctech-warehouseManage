<?php

namespace Tests\Feature;

use App\Jobs\DeletePublicFiles;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Reimbursement;
use App\Models\Report;
use App\Models\Salary;
use App\Models\Shift;
use App\Models\Site;
use App\Models\User;
use App\Services\ImportAmount;
use App\Services\IndonesianHolidayService;
use App\Services\PayrollCalculation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuditRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
        Storage::fake('public');
    }

    private function site(): Site
    {
        $branch = Branch::create(['branch_name' => 'Branch', 'branch_code' => uniqid()]);

        return Site::create(['branch_id' => $branch->id, 'machine_name' => bin2hex(random_bytes(4)).' Machine', 'slug' => uniqid('site-'), 'location' => 'Jakarta']);
    }

    private function user(string $role, ?Site $site = null): User
    {
        return User::factory()->create(['role' => $role, 'site_id' => $site?->id]);
    }

    private function employee(Site $site): Employee
    {
        return Employee::create(['name' => 'Engineer', 'site_id' => $site->id, 'branch_id' => $site->branch_id, 'phone_number' => '123', 'mcu' => 'no', 'tld' => 'no', 'basic_salary' => 5000000, 'bank_account_number' => '123456789', 'nik' => '1234567890123456']);
    }

    private function claim(User $user): Reimbursement
    {
        return Reimbursement::create(['user_id' => $user->id, 'person_name' => 'Owner', 'date' => '2026-09-01', 'category' => 'office', 'amount' => 100, 'receipt_attachment' => 'receipts/'.uniqid().'.png', 'status' => 'pending']);
    }

    public function test_fast_approve_is_superadmin_only_and_terminal_states_are_locked(): void
    {
        $claim = $this->claim($this->user('employee_role', $this->site()));
        foreach (['team_leader', 'station_master', 'manager', 'administration', 'employee_role'] as $role) {
            $this->actingAs($this->user($role, $this->site()))->post('/reimbursements/'.$claim->id.'/fast-approve')->assertForbidden();
        }
        $this->assertSame('pending', $claim->fresh()->status);
        $admin = $this->user('superadmin');
        $this->actingAs($admin)->post('/reimbursements/'.$claim->id.'/fast-approve')->assertRedirect();
        $this->assertSame($admin->id, $claim->fresh()->approved_by);
        $this->post('/reimbursements/'.$claim->id.'/fast-approve')->assertStatus(409);
        $claim->update(['status' => 'rejected']);
        $this->post('/reimbursements/'.$claim->id.'/fast-approve')->assertStatus(409);
    }

    public function test_master_site_changes_require_superadmin_and_keep_slug(): void
    {
        $site = $this->site();
        $slug = $site->slug;
        $payload = ['branch_id' => $site->branch_id, 'machine_name' => 'Changed', 'location' => 'Jakarta'];
        $this->actingAs($this->user('team_leader', $this->site()));
        foreach (['sites', 'site'] as $prefix) {
            $this->put('/'.$prefix.'/'.$site->id, $payload)->assertForbidden();
            $this->delete('/'.$prefix.'/'.$site->id)->assertForbidden();
            $this->post('/'.$prefix, $payload)->assertForbidden();
        }
        $this->actingAs($this->user('superadmin'))->put('/sites/'.$site->id, $payload)->assertRedirect();
        $this->assertSame('Changed', $site->fresh()->machine_name);
        $this->assertSame($slug, $site->fresh()->slug);
    }

    public function test_reports_remain_visible_after_site_rename_and_are_scoped(): void
    {
        $site = $this->site();
        $leader = $this->user('team_leader', $site);
        $payload = ['attendant' => 'UniqueAttendant', 'site_machine' => $site->slug, 'failure_date' => '2026-09-01', 'ts_procedure' => 'Procedure', 'failed_subsystem' => 'Subsystem', 'failure_phenomenon' => 'Failure'];
        $this->actingAs($leader)->post('/report', $payload)->assertSessionHasNoErrors();
        $report = Report::firstOrFail();
        $this->assertSame($site->id, $report->site_id);
        $site->update(['machine_name' => 'Renamed']);
        $this->get('/report/'.$site->slug)->assertOk()->assertViewHas('report', fn ($rows) => $rows->total() === 1);
        $this->get('/report/'.$report->id.'/edit')->assertOk();
        $this->put('/report/'.$report->id, $payload)->assertSessionHasNoErrors();
        $this->assertCount(1, (new \App\Exports\ReportExport)->collection());
        $this->actingAs($this->user('team_leader', $this->site()));
        $this->withHeader('X-Requested-With', 'XMLHttpRequest')->post('/report/search', ['query' => 'UniqueAttendant'])->assertOk()->assertDontSee('UniqueAttendant');
        $this->assertCount(0, (new \App\Exports\ReportExport)->collection());
        $this->put('/report/'.$report->id, $payload)->assertForbidden();
        $this->delete('/report/'.$report->id)->assertForbidden();
    }

    public function test_schedule_access_rejects_other_sites_and_unassigned_users(): void
    {
        $site = $this->site();
        $employee = $this->employee($site);
        $shift = Shift::create(['shift_name' => 'Day', 'start_time' => '08:00', 'end_time' => '16:00']);
        foreach ([$this->user('team_leader', $this->site()), $this->user('team_leader')] as $user) {
            $this->actingAs($user);
            $this->get('/schedule/export?site_id='.$site->id.'&month=9&year=2026')->assertForbidden();
            $this->post('/schedules/site-pattern/'.$site->id, ['schedule_type' => 'office_hour', 'work_days' => 5, 'off_days' => 2])->assertForbidden();
            $this->postJson('/schedule/update-single', ['employee_id' => $employee->id, 'date' => '2026-09-01', 'shift_id' => $shift->id])->assertForbidden();
            $this->post('/schedules/generate', ['target_site_ids' => [$site->id], 'employee_ids' => [$employee->id], 'month' => 9, 'year' => 2026, 'start_day' => 1, 'schedule_type' => 'office_hour'])->assertForbidden();
        }
        $this->assertDatabaseCount('site_schedules', 0);
        $this->assertDatabaseCount('employee_schedules', 0);
        $this->actingAs($this->user('team_leader', $site))->post('/schedules/site-pattern/'.$site->id, ['schedule_type' => 'office_hour', 'work_days' => 5, 'off_days' => 2])->assertSessionHasNoErrors();
    }

    public function test_bulk_delete_denied_batch_preserves_all_files_and_records(): void
    {
        Queue::fake();
        $user = $this->user('employee_role', $this->site());
        $mine = $this->claim($user);
        $other = $this->claim($this->user('employee_role', $this->site()));
        foreach ([$mine, $other] as $claim) {
            $claim->delete();
            Storage::disk('public')->put($claim->receipt_attachment, 'test');
        }
        $this->actingAs($user)->delete('/reimbursements/recycle-bin/bulk-force-delete', ['ids' => [$mine->id, $other->id]])->assertForbidden();
        foreach ([$mine, $other] as $claim) {
            $this->assertSoftDeleted($claim);
            Storage::disk('public')->assertExists($claim->receipt_attachment);
        }
        Queue::assertNothingPushed();
        $this->delete('/reimbursements/recycle-bin/bulk-force-delete', ['ids' => [$mine->id]])->assertRedirect();
        $this->assertDatabaseMissing('reimbursements', ['id' => $mine->id]);
        Queue::assertPushed(DeletePublicFiles::class, fn ($job) => $job->afterCommit && $job->paths === [$mine->receipt_attachment]);
        $job = new DeletePublicFiles([$mine->receipt_attachment]);
        $job->handle();
        $job->handle();
        Storage::disk('public')->assertMissing($mine->receipt_attachment);
        Storage::disk('public')->assertExists($other->receipt_attachment);
    }

    public function test_attendance_payload_excludes_personal_and_payroll_attributes(): void
    {
        $site = $this->site();
        $employee = $this->employee($site);
        $response = $this->actingAs($this->user('employee_role', $site))->getJson('/api/branches/'.$site->id.'/employees?month=2026-09')->assertOk();
        $response->assertJsonPath('0.id', $employee->id)->assertJsonPath('0.name', 'Engineer');
        foreach (['nik', 'bank_account_number', 'basic_salary', 'email', 'phone_number'] as $field) {
            $this->assertArrayNotHasKey($field, $response->json('0'));
        }
        $this->actingAs($this->user('team_leader', $this->site()))->getJson('/api/branches/'.$site->id.'/employees')->assertForbidden();
    }

    public function test_signed_claim_is_immutable_but_unsigned_claim_can_be_edited(): void
    {
        $user = $this->user('employee_role', $this->site());
        $claim = $this->claim($user);
        $claim->status = 'pending_manager';
        $claim->signatures_json = [['signer_name' => 'Leader']];
        $claim->save();
        $payload = ['person_name' => 'Owner', 'date' => '2026-09-01', 'category' => 'office', 'amount' => '9000000'];
        $this->actingAs($user)->put('/reimbursements/'.$claim->id, $payload)->assertStatus(409);
        $this->get('/reimbursements/'.$claim->id.'/edit')->assertStatus(409);
        $this->assertEquals(100, $claim->fresh()->amount);
        $unsigned = $this->claim($user);
        $this->put('/reimbursements/'.$unsigned->id, $payload)->assertSessionHasNoErrors();
        $this->assertEquals(9000000, $unsigned->fresh()->amount);
    }

    public function test_payroll_regeneration_preserves_resigned_employee_history_and_amount(): void
    {
        $employee = $this->employee($this->site());
        $employee->update(['is_active' => false, 'status' => 'Resigned', 'resign_date' => '2026-09-30', 'join_date' => '2026-01-01']);
        $salary = Salary::create(['employee_id' => $employee->id, 'name' => 'Engineer', 'position' => 'Engineer', 'placement' => 'Jakarta', 'bank' => 'Bank', 'account_no' => '123', 'amount' => 4000000, 'information' => 'regular salary']);
        $salary->created_at = '2026-08-15 12:00:00';
        $salary->save();
        $this->actingAs($this->user('superadmin'))->post('/salary/generate-monthly', ['month' => 8, 'year' => 2026])->assertSessionHasNoErrors();
        $this->assertEquals(4000000, $salary->fresh()->amount);
        $this->post('/salary/generate-monthly', ['month' => 10, 'year' => 2026])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('salaries', 1);
        $this->post('/salary/generate-monthly', ['month' => 13, 'year' => 2026])->assertSessionHasErrors('month');
    }

    public function test_import_amount_preserves_indonesian_text_and_numeric_cells(): void
    {
        $this->actingAs($this->user('employee_role', $this->site()));
        (new \App\Imports\ReimbursementImport)->collection(collect([[], [], [1, 'office', '2026-09-01', '', '', 'Owner', '1.000.000', '']]));
        $this->assertEquals(1000000, Reimbursement::firstOrFail()->amount);
        $this->assertSame(1000000.50, ImportAmount::parse('Rp 1.000.000,50', 3));
        $this->assertSame(1000000.50, ImportAmount::parse(1000000.50, 3));
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        ImportAmount::parse('1,000,000', 3);
    }

    public function test_holiday_failure_preserves_last_valid_calendar_and_retries(): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(['*' => Http::response(['data' => [['date' => '2026-09-01', 'description' => 'Holiday']]])]);
        $service = app(IndonesianHolidayService::class);
        $this->assertSame(['2026-09-01' => 'Holiday'], $service->getHolidays(2026));
        Cache::forget('id_holidays_2026');
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(['*' => Http::response([], 503)]);
        $this->assertSame(['2026-09-01' => 'Holiday'], $service->getHolidays(2026));
        $this->assertFalse(Cache::has('id_holidays_2026'));
        $this->assertTrue(Cache::has('id_holidays_2026_retry'));
        $this->travel(6)->minutes();
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(['*' => Http::response(['data' => [['date' => '2026-09-02', 'description' => 'Updated']]])]);
        $this->assertSame(['2026-09-02' => 'Updated'], $service->getHolidays(2026));
    }

    public function test_payroll_counts_recorded_attendance_not_planned_holiday_shifts(): void
    {
        $employee = $this->employee($this->site());
        Cache::put('id_holidays_2026', ['2026-09-01' => 'Holiday']);
        $shift = Shift::create(['shift_name' => 'Day', 'start_time' => '08:00', 'end_time' => '16:00']);
        EmployeeSchedule::create(['employee_id' => $employee->id, 'date' => '2026-09-01', 'shift_id' => $shift->id]);
        $service = app(PayrollCalculation::class);
        $this->assertSame(0, $service->calculate($employee->id, '2026-09', 4000000)['holiday_overtime_days']);
        Attendance::create(['employee_id' => $employee->id, 'month' => '2026-09', 'working_days' => 1, 'attendance_count' => 1, 'matrix_details' => json_encode([1 => ['s1' => 1, 's2' => 0, 's3' => 0]])]);
        $this->assertSame(1, $service->calculate($employee->id, '2026-09', 4000000)['holiday_overtime_days']);
        $employee->salaryHistories()->create(['old_salary' => 4000000, 'new_salary' => 5000000, 'reason' => 'Raise', 'updated_by' => $this->user('superadmin')->id]);
        $this->assertSame(4000000.0, $service->salaryAt($employee, \Carbon\Carbon::parse('2026-08-31')));
    }

    public function test_recycle_bin_list_matches_management_rights(): void
    {
        $claim = $this->claim($this->user('employee_role', $this->site()));
        $claim->delete();
        $this->actingAs($this->user('manager'))->get('/reimbursements/trash/archive')->assertOk()->assertViewHas('reimbursements', fn ($rows) => $rows->total() === 0);
        $this->actingAs($this->user('administration'))->get('/reimbursements/trash/archive')->assertOk()->assertViewHas('reimbursements', fn ($rows) => $rows->total() === 1);
    }

    public function test_report_migration_maps_unique_legacy_values_without_guessing(): void
    {
        $migration = require database_path('migrations/2026_10_11_000001_add_site_id_to_reports.php');
        $migration->down();
        $first = $this->site();
        $second = $this->site();
        $second->update(['machine_name' => $first->machine_name]);
        $third = $this->site();
        $ids = [];
        foreach ([$first->slug, $third->machine_name, $first->machine_name, 'Unknown'] as $value) {
            $ids[] = \Illuminate\Support\Facades\DB::table('reports')->insertGetId([
                'attendant' => 'A', 'site_machine' => $value, 'failure_date' => '2026-09-01',
                'failure_note' => 'Test', 'ts_procedure' => 'Test',
            ]);
        }
        $migration->up();
        $this->assertDatabaseHas('reports', ['id' => $ids[0], 'site_id' => $first->id]);
        $this->assertDatabaseHas('reports', ['id' => $ids[1], 'site_id' => $third->id]);
        $this->assertDatabaseHas('reports', ['id' => $ids[2], 'site_id' => null]);
        $this->assertDatabaseHas('reports', ['id' => $ids[3], 'site_id' => null]);
    }

    public function test_dashboard_counters_remain_scoped_and_measure_stock_quantity(): void
    {
        $site = $this->site();
        $other = $this->site();
        $part = \App\Models\Sparepart::create(['item_name' => 'Part', 'type' => 'T', 'uom' => 'pcs']);
        foreach ([$site->id => 7, $other->id => 100] as $id => $qty) {
            \App\Models\SparepartStock::create(['sparepart_id' => $part->id, 'site_id' => $id, 'condition' => 'new', 'qty' => $qty]);
        }
        $this->actingAs($this->user('superadmin'))->get('/dashboard')->assertOk()->assertViewHas('totalSparepart', 107)->assertViewHas('totalBranch', 2);
        $this->actingAs($this->user('team_leader', $site))->get('/dashboard')->assertOk()->assertViewHas('totalSparepart', 7)->assertViewHas('totalMachine', 1);
    }

    public function test_schedule_family_is_consistent_across_listing_and_mutation(): void
    {
        $one = $this->site();
        $one->update(['machine_name' => 'ABCDE One']);
        $two = $this->site();
        $two->update(['machine_name' => 'ABCDE Two']);
        $this->actingAs($this->user('team_leader', $one));
        $this->get('/schedules?site_id='.$two->id)->assertOk();
        $this->get('/schedule/export?site_id='.$two->id)->assertOk()->assertDownload();
        $this->post('/schedules/site-pattern/'.$two->id, ['schedule_type' => 'office_hour', 'work_days' => 5, 'off_days' => 2])->assertSessionHasNoErrors();
    }

    public function test_unassigned_employee_cannot_read_attendance(): void
    {
        $this->actingAs($this->user('employee_role'));
        $this->get('/attendance')->assertForbidden();
        $this->getJson('/api/branches/all/employees')->assertForbidden();
    }
}
