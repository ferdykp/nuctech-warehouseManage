<?php

namespace Tests\Feature;

use App\Exports\SalaryExport;
use App\Models\{Branch, Employee, Salary, Site, User};
use App\Services\PayrollCalculation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Http, Storage};
use Tests\TestCase;

class MonthlyAllowanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 20));
        Http::fake();
        Storage::fake('local');
        $this->actingAs(User::factory()->create(['role' => 'administration']));
    }

    private function employee(): Employee
    {
        $branch = Branch::create(['branch_name' => 'Jakarta', 'branch_code' => uniqid()]);
        $site = Site::create(['branch_id' => $branch->id, 'machine_name' => 'Machine', 'slug' => uniqid(), 'location' => 'Jakarta']);
        return Employee::create(['name' => 'Karyawan A', 'site_id' => $site->id, 'branch_id' => $branch->id,
            'phone_number' => '123', 'mcu'=>'no', 'tld'=>'no', 'basic_salary' => 7000000, 'bank_name' => 'BCA', 'bank_account_number' => '00123',
            'join_date' => '2025-01-01', 'is_active' => true, 'status' => 'Contract']);
    }

    private function generate(string $month): void
    {
        $this->post(route('salary.generateMonthly'), ['month' => $month, 'year' => 2026])->assertRedirect();
    }

    public function test_monthly_allowance_is_paid_together_and_snapshot_survives_changes(): void
    {
        $employee = $this->employee();
        $employee->allowances()->create(['name' => 'PPR', 'amount' => 1000000, 'start_month' => '2026-10', 'end_month' => '2026-11']);
        $this->generate('10');
        $salary = Salary::firstOrFail();
        $this->assertEquals(7000000, $salary->amount);
        $this->assertEquals(1000000, $salary->allowance_total);
        $this->get(route('salary.show', $salary).'?month=09&year=2020')->assertOk()
            ->assertJsonPath('calculation.total_salary_to_pay', 'Rp 8.000.000')
            ->assertJsonPath('calculation.allowances.0.name', 'PPR');
        $employee->allowances()->update(['amount' => 2000000]);
        $this->generate('10');
        $this->assertEquals(1000000, $salary->fresh()->allowance_total);
        $this->generate('11');
        $this->assertEquals(2000000, Salary::whereMonth('created_at', 11)->firstOrFail()->allowance_total);
        $this->generate('12');
        $this->assertEquals(0, Salary::whereMonth('created_at', 12)->firstOrFail()->allowance_total);
        $this->assertEquals(7000000, $employee->fresh()->basic_salary);
        $salary->update(['before_after' => 'Salary adjustment', 'get_information' => 'Payroll note']);
        $export = new SalaryExport('10', 2026);
        $row = $export->map($export->collection()->first());
        $this->assertEquals(8000000, $row[5]);
        $this->assertEquals('00123', $row[4]);
        $this->assertEquals(7000000, $row[11]);
        $this->assertEquals(1000000, $row[12]);
        $this->assertSame('Salary adjustment', $row[7]);
        $this->assertSame('Payroll note', $row[10]);
        $this->assertSame('PPR : Rp 1.000.000', $row[13]);
        $this->assertCount(count($row), $export->headings()[1]);

        $workbook = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
        $sheet = $workbook->getActiveSheet();
        $sheet->fromArray($export->headings());
        $sheet->fromArray([$row], null, 'A3');
        $export->styles($sheet);
        $this->assertSame('A2:N3', $sheet->getAutoFilter()->getRange());
        $this->assertContains('A1:N1', $sheet->getMergeCells());
    }

    public function test_multiple_allowances_boundaries_and_legacy_payroll(): void
    {
        $employee = $this->employee();
        foreach ([['PPR',1000000,'2026-10','2026-10'], ['Jabatan',500000,'2026-09',null], ['Mendatang',900000,'2026-11',null]] as [$name,$amount,$start,$end]) {
            $employee->allowances()->create(['name'=>$name,'amount'=>$amount,'start_month'=>$start,'end_month'=>$end]);
        }
        $calc = app(PayrollCalculation::class);
        $this->assertCount(2, $calc->allowancesAt($employee, '2026-10'));
        $this->generate('10');
        $salary = Salary::firstOrFail();
        $this->assertEquals(1500000, $salary->allowance_total);
        $salary->update(['allowance_snapshot' => null]);
        $this->get(route('salary.show', $salary))->assertJsonPath('calculation.total_salary_to_pay', 'Rp 7.000.000');
        $this->put(route('salary.update', $salary), ['employee_id'=>$employee->id,'bank'=>'BCA','account_no'=>'00123','amount'=>7000000,'information'=>'regular salary','refresh_allowances'=>1])->assertRedirect();
        $this->assertEquals(1500000, $salary->fresh()->allowance_total);
    }

    public function test_employee_detail_includes_all_allowances_and_only_current_total(): void
    {
        $employee = $this->employee();
        $employee->update(['contract_start_date' => '2025-01-02']);
        foreach ([['PPR',1000000,'2026-10',null], ['Lama',500000,'2026-01','2026-09'], ['Mendatang',2000000,'2026-11',null]] as [$name,$amount,$start,$end]) {
            $employee->allowances()->create(['name'=>$name,'amount'=>$amount,'start_month'=>$start,'end_month'=>$end,'notes'=>'Catatan tunjangan']);
        }
        $this->get(route('employee.show', $employee))->assertOk()
            ->assertJsonPath('allowance_total_formatted', 'Rp 1.000.000')
            ->assertJsonPath('monthly_total_formatted', 'Rp 8.000.000')
            ->assertJsonPath('contract_start_formatted', '02 Januari 2025')
            ->assertJsonCount(3, 'allowance_details')
            ->assertJsonMissingPath('allowances');
        $this->actingAs(User::factory()->create(['role'=>'employee_role']));
        $this->get(route('employee.show', $employee))->assertForbidden();
    }

    public function test_allowance_management_validates_and_protects_certificate(): void
    {
        $employee = $this->employee();
        $this->from(route('employee.edit',$employee))->post(route('employee.allowances.store',$employee), [
            'name'=>'PPR','amount'=>1000000,'start_month'=>'2026-10','certificate'=>UploadedFile::fake()->create('ppr.pdf',10,'application/pdf'),
        ])->assertRedirect();
        $allowance = $employee->allowances()->firstOrFail();
        Storage::disk('local')->assertExists($allowance->certificate_path);
        $this->get(route('employee.edit',$employee))->assertOk()->assertSee('Tunjangan Bulanan');
        $this->get(route('employee.allowances.certificate',[$employee,$allowance]))->assertDownload();
        $this->put(route('employee.allowances.end',[$employee,$allowance]), ['end_month'=>'2026-09'])->assertSessionHasErrors('end_month');
        $this->put(route('employee.allowances.end',[$employee,$allowance]), ['end_month'=>'2026-10'])->assertRedirect();
        $other = $this->employee();
        $this->get(route('employee.allowances.certificate',[$other,$allowance]))->assertNotFound();
        $this->post(route('employee.allowances.store',$employee), ['name'=>'PPR','amount'=>-1,'start_month'=>'2026-13'])->assertSessionHasErrors(['amount','start_month']);
        $this->actingAs(User::factory()->create(['role'=>'employee_role']));
        $this->post(route('employee.allowances.store',$employee), [])->assertForbidden();
        $this->get(route('employee.allowances.certificate',[$employee,$allowance]))->assertForbidden();
    }
}
