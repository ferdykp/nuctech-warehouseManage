<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\Site;
use App\Models\User;
use App\Services\Contracts\ContractRenewalList;
use App\Services\Contracts\RenewalListDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ContractRenewalListTest extends TestCase
{
    use RefreshDatabase;

    private function employee(array $attributes = []): Employee
    {
        $b = Branch::firstOrCreate(['branch_code' => 'REN'], ['branch_name' => 'Jakarta']);
        $s = Site::firstOrCreate(['slug' => 'renewal-machine'], ['branch_id' => $b->id, 'machine_name' => 'Machine A', 'location' => 'Terminal']);
        $i = Employee::count() + 1;

        return Employee::create(array_merge(['branch_id' => $b->id, 'site_id' => $s->id, 'name' => 'Renewal Employee '.$i, 'nik' => str_pad((string) $i, 16, '0', STR_PAD_LEFT), 'phone_number' => '0800000', 'mcu' => 'no', 'tld' => 'no', 'position' => 'Project Engineer', 'status' => 'Contract', 'is_active' => true, 'basic_salary' => '5500000.00', 'bank_name' => 'BCA', 'bank_account_number' => '00123456789', 'pkwt_date' => '2025-10-15'], $attributes));
    }

    private function filters(array $data = []): array
    {
        return app(ContractRenewalList::class)->filters(Request::create('/contracts', 'GET', array_merge(['year' => 2026, 'month' => 10], $data)));
    }

    public function test_legacy_anniversary_requires_a_full_year_and_uses_current_position_bank_and_salary(): void
    {
        $yes = $this->employee();
        $this->employee(['pkwt_date' => '2026-10-01']);
        $this->employee(['pkwt_date' => '2025-09-20']);
        $this->employee(['is_active' => false]);
        $rows = app(ContractRenewalList::class)->rows($this->filters());
        $this->assertCount(1, $rows);
        $this->assertSame($yes->id, $rows[0]['employee']->id);
        $this->assertTrue($rows[0]['legacy']);
        $this->assertSame('2026-10-15', $rows[0]['date']->toDateString());
        $range = app(ContractRenewalList::class)->rows($this->filters(['month' => 9, 'month_end' => 10]));
        $this->assertCount(2, $range);
    }

    public function test_verified_expiry_supersedes_anniversary_and_approved_renewal_is_excluded_by_default(): void
    {
        $e = $this->employee();
        $old = EmployeeContract::create(['employee_id' => $e->id, 'status' => 'active', 'start_date' => '2025-12-01', 'end_date' => '2026-11-30', 'contract_number' => 'OLD']);
        $service = app(ContractRenewalList::class);
        $this->assertCount(0, $service->rows($this->filters()));
        $nov = $this->filters(['month' => 11]);
        $this->assertCount(1, $service->rows($nov));
        $draft = EmployeeContract::create(['employee_id' => $e->id, 'status' => 'draft', 'previous_contract_id' => $old->id, 'start_date' => '2026-12-01', 'end_date' => '2027-11-30', 'contract_number' => 'NEW']);
        $this->assertCount(1, $service->rows($this->filters(['month' => 11, 'status' => 'draft'])));
        $draft->update(['status' => 'active']);
        $this->assertCount(0, $service->rows($nov));
        $all = $service->rows($this->filters(['month' => 11, 'renewal_state' => 'all']));
        $this->assertCount(1, $all);
        $this->assertTrue($all[0]['approved']);
        $this->assertCount(1, $service->rows($this->filters(['month' => 11, 'renewal_state' => 'approved'])));
    }

    public function test_combined_filters_and_single_employee_deduplication(): void
    {
        $e = $this->employee();
        $this->employee(['position' => 'Marketing']);
        $service = app(ContractRenewalList::class);
        EmployeeContract::create(['employee_id' => $e->id, 'status' => 'needs_verification']);
        EmployeeContract::create(['employee_id' => $e->id, 'status' => 'draft']);
        $rows = $service->rows($this->filters(['branch_id' => $e->branch_id, 'site_id' => $e->site_id, 'position' => 'Project Engineer', 'employment_status' => 'Contract', 'search' => 'Employee 1']));
        $this->assertCount(1, $rows);
        $this->assertSame($e->id, $rows[0]['employee']->id);
        $other = Site::create(['branch_id' => $e->branch_id, 'machine_name' => 'Machine B', 'slug' => 'machine-b', 'location' => 'Test']);
        $this->assertCount(0, $service->rows($this->filters(['site_id' => $other->id])));
    }

    public function test_docx_matches_filtered_rows_preserves_leading_zero_and_total_and_has_no_sample_identities(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'superadmin']));
        $e = $this->employee(['name' => 'A & <B>', 'basic_salary' => '5500000.50']);
        $this->employee(['pkwt_date' => '2025-09-01']);
        $filters = $this->filters();
        $service = app(ContractRenewalList::class);
        $rows = $service->rows($filters);
        $path = app(RenewalListDocument::class)->generate($rows, $service->period($filters, 'en'));
        try {
            $z = new \ZipArchive;
            $this->assertTrue($z->open($path));
            $xml = $z->getFromName('word/document.xml');
            $d = new \DOMDocument;
            $this->assertTrue($d->loadXML($xml));
            $text = $d->textContent;
            foreach (['October 2026', 'Project Engineer', 'A & <B> *', '00123456789', 'Rp5.500.000,50'] as $value) {
                $this->assertStringContainsString($value, $text);
            }
            foreach (['Irwinsyah', '2711232151', 'Rp75.000.000', '${'] as $value) {
                $this->assertStringNotContainsString($value, $xml);
            }
            $this->assertSame(2, substr_count($text, 'Rp5.500.000,50'));
            $z->close();
        } finally {
            @unlink($path);
        }
        $query = ['year' => 2026, 'month' => 10];
        $this->get(route('contracts.index', $query))->assertOk()->assertSee('A &amp; &lt;B&gt;', false);
        $this->get(route('contracts.renewals.export', $query))->assertOk()->assertSee('00123456789');
        $this->get(route('contracts.renewals.download', $query))->assertOk()->assertDownload('Remuneration-PKWT-2026-10-10.docx');
    }

    public function test_incomplete_data_empty_lists_validation_and_authorization(): void
    {
        $e = $this->employee(['bank_name' => null]);
        $query = ['year' => 2026, 'month' => 10];
        foreach (['employee_role', 'team_leader', 'station_master', 'manager'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]));
            $this->get(route('contracts.renewals.export', $query))->assertForbidden();
            $this->get(route('contracts.renewals.download', $query))->assertForbidden();
        }
        $this->actingAs(User::factory()->create(['role' => 'administration']));
        $this->get(route('contracts.renewals.download', $query))->assertRedirect(route('contracts.renewals.export', array_merge($this->filters(), [])))->assertSessionHas('error');
        $this->get(route('contracts.renewals.download', $query + ['allow_incomplete' => 1]))->assertOk();
        $this->get(route('contracts.index', ['year' => 2026, 'month' => 13]))->assertSessionHasErrors('month');
        $this->get(route('contracts.index', ['year' => 2026, 'month' => 10, 'month_end' => 9]))->assertSessionHasErrors('month_end');
        $this->get(route('contracts.renewals.download', ['year' => 2026, 'month' => 8]))->assertRedirect()->assertSessionHas('error');
    }

    public function test_export_contains_all_pages_of_filtered_results_and_leap_day_is_clamped(): void
    {
        for ($i = 0; $i < 18; $i++) {
            $this->employee();
        }
        $this->actingAs(User::factory()->create(['role' => 'superadmin']));
        $query = ['year' => 2026, 'month' => 10];
        $response = $this->get(route('contracts.index', $query));
        $response->assertOk()->assertViewHas('candidates', fn ($p) => $p->total() === 18 && $p->count() === 15);
        $this->get(route('contracts.renewals.export', $query))->assertViewHas('rows', fn ($r) => $r->count() === 18);
        $e = $this->employee(['pkwt_date' => '2024-02-29']);
        $rows = app(ContractRenewalList::class)->rows($this->filters(['month' => 2, 'year' => 2025, 'employee_id' => $e->id]));
        $this->assertSame('2025-02-28',$rows[0]['date']->toDateString());
    }
}
