<?php

namespace Tests\Feature;

use App\Models\{Branch, Employee, EmployeeSalaryHistory, Salary, SalaryHistoryAudit, Site, User};
use App\Services\{PayrollCalculation, SalaryHistoryChanges};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SalaryHistoryCorrectionTest extends TestCase
{
    use RefreshDatabase;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
        $this->actingAs(User::factory()->create(['role'=>'administration']));
        $branch = Branch::create(['branch_name'=>'Jakarta','branch_code'=>uniqid()]);
        $site = Site::create(['branch_id'=>$branch->id,'machine_name'=>'Machine','slug'=>uniqid(),'location'=>'Jakarta']);
        $this->employee = Employee::create(['name'=>'Karyawan A','site_id'=>$site->id,'branch_id'=>$branch->id,'phone_number'=>'123','mcu'=>'no','tld'=>'no','basic_salary'=>7000000,'status'=>'Contract','join_date'=>'2025-01-01']);
    }

    private function history($old, $new, string $date): EmployeeSalaryHistory
    {
        $row = $this->employee->salaryHistories()->create(['old_salary'=>$old,'new_salary'=>$new,'reason'=>'Gaji Awal','updated_by'=>'HR']);
        $row->created_at = $date;
        $row->save();
        return $row;
    }

    private function requestCorrection(EmployeeSalaryHistory $history, array $overrides = [])
    {
        return $this->put(route('employee.salary-history.update',[$this->employee,$history]), array_replace([
            'action'=>'correct','version'=>app(SalaryHistoryChanges::class)->version($this->employee->fresh()),
            'amount'=>6000000,'entry_reason'=>'Gaji Awal yang Benar','correction_reason'=>'Salah ketik nominal',
        ], $overrides));
    }

    public function test_correcting_initial_input_updates_same_record_and_preserves_payroll(): void
    {
        $history = $this->history(0,7000000,'2026-09-01');
        $salary = Salary::create(['employee_id'=>$this->employee->id,'name'=>'A','bank'=>'BCA','account_no'=>'001','amount'=>7000000,'information'=>'regular salary']);
        $this->requestCorrection($history)->assertRedirect();
        $this->assertEquals(6000000,$this->employee->fresh()->basic_salary);
        $this->assertEquals(6000000,$history->fresh()->new_salary);
        $this->assertEquals(7000000,$salary->fresh()->amount);
        $this->assertEquals(7000000,SalaryHistoryAudit::firstOrFail()->before['histories'][0]['new_salary']);
        $this->assertEquals(1,$this->employee->salaryHistories()->count());
        $this->assertEquals(6000000,app(PayrollCalculation::class)->salaryAt($this->employee->fresh(),now()->setDate(2026,9,30)));
    }

    public function test_correcting_older_record_repairs_chain_without_overwriting_later_raise(): void
    {
        $first = $this->history(0,7000000,'2026-09-01');
        $next = $this->history(7000000,8000000,'2026-10-01');
        $this->employee->update(['basic_salary'=>8000000]);
        $this->requestCorrection($first)->assertRedirect();
        $this->assertEquals(6000000,$next->fresh()->old_salary);
        $this->assertEquals(8000000,$this->employee->fresh()->basic_salary);
        $this->assertEquals(6000000,app(PayrollCalculation::class)->salaryAt($this->employee->fresh(),now()->setDate(2026,9,30)));
    }

    public function test_cancellation_keeps_record_and_restores_previous_active_salary(): void
    {
        $this->history(0,6000000,'2026-09-01');
        $latest = $this->history(6000000,7000000,'2026-10-01');
        $this->requestCorrection($latest,['action'=>'cancel','confirm_cancel'=>1])->assertRedirect();
        $this->assertNotNull($latest->fresh()->cancelled_at);
        $this->assertEquals(6000000,$this->employee->fresh()->basic_salary);
        $this->assertEquals(6000000,app(PayrollCalculation::class)->salaryAt($this->employee->fresh(),now()->setDate(2026,10,31)));
        $this->assertEquals(2,$this->employee->salaryHistories()->count());
        $this->requestCorrection($latest)->assertSessionHasErrors('history');
    }

    public function test_validation_stale_submissions_and_permissions_cannot_modify_salary(): void
    {
        $history = $this->history(0,7000000,'2026-09-01');
        $this->requestCorrection($history,['correction_reason'=>''])->assertSessionHasErrors('correction_reason');
        $this->requestCorrection($history,['version'=>str_repeat('a',64)])->assertSessionHasErrors('version');
        $this->requestCorrection($history,['action'=>'cancel'])->assertSessionHasErrors('confirm_cancel');
        $this->requestCorrection($history,['amount'=>-1])->assertSessionHasErrors('amount');
        $this->assertEquals(7000000,$this->employee->fresh()->basic_salary);
        $this->get(route('employee.salary-history.edit',[$this->employee,$history]))->assertOk()->assertSee('Gaji pokok setelah koreksi');
        $this->actingAs(User::factory()->create(['role'=>'employee_role']));
        $this->requestCorrection($history)->assertForbidden();
        $this->get(route('employee.salary-history.edit',[$this->employee,$history]))->assertForbidden();
    }

    public function test_cancelling_middle_history_repairs_next_old_amount_and_keeps_current_salary(): void
    {
        $this->history(0,5000000,'2026-08-01');
        $middle = $this->history(5000000,6000000,'2026-09-01');
        $last = $this->history(6000000,7000000,'2026-10-01');
        $this->requestCorrection($middle,['action'=>'cancel','confirm_cancel'=>1])->assertRedirect();
        $this->assertEquals(5000000,$last->fresh()->old_salary);
        $this->assertEquals(7000000,$this->employee->fresh()->basic_salary);
        $this->assertEquals(5000000,app(PayrollCalculation::class)->salaryAt($this->employee->fresh(),now()->setDate(2026,9,30)));
    }

    public function test_cancelling_only_history_preserves_original_baseline_and_rejects_wrong_employee(): void
    {
        $history = $this->history(4000000,7000000,'2026-09-01');
        $other = $this->employee->replicate();
        $other->name='Other';
        $other->save();
        $this->get(route('employee.salary-history.edit',[$other,$history]))->assertNotFound();
        $this->put(route('employee.salary-history.update',[$other,$history]),[])->assertNotFound();
        $this->requestCorrection($history,['action'=>'cancel','confirm_cancel'=>1])->assertRedirect();
        $this->assertEquals(4000000,$this->employee->fresh()->basic_salary);
        $this->assertEquals(4000000,app(PayrollCalculation::class)->salaryAt($this->employee->fresh(),now()));
    }

    public function test_employee_edit_distinguishes_correction_from_real_salary_change(): void
    {
        $this->history(0,7000000,'2026-09-01');
        $fields = ['site_id'=>$this->employee->site_id,'name'=>'A','phone_number'=>'123','status'=>'Contract','join_date'=>'2025-01-01','basic_salary'=>6000000,'salary_change_mode'=>'correction','salary_change_reason'=>'Salah input'];
        $this->put(route('employee.update',$this->employee),$fields)->assertRedirect();
        $this->assertEquals(1,$this->employee->salaryHistories()->count());
        $this->assertEquals(6000000,$this->employee->fresh()->basic_salary);
        $fields['basic_salary']=8000000;
        $fields['salary_change_mode']='change';
        $fields['salary_change_reason']='Promosi';
        $this->put(route('employee.update',$this->employee),$fields)->assertRedirect();
        $this->assertEquals(2,$this->employee->salaryHistories()->count());
        $this->assertEquals(8000000,$this->employee->fresh()->basic_salary);
    }
}
