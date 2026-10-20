<?php

namespace App\Http\Controllers;

use App\Models\{Employee, EmployeeSalaryHistory, SalaryHistoryAudit};
use App\Services\SalaryHistoryChanges;
use Illuminate\Http\Request;

class SalaryHistoryController extends Controller
{
    public function edit(Employee $employee, EmployeeSalaryHistory $history, SalaryHistoryChanges $changes)
    {
        abort_unless($history->employee_id === $employee->id, 404);
        $active = $employee->salaryHistories()->whereNull('cancelled_at')->reorder()->orderBy('created_at')->orderBy('id')->get();
        $isLatest = $active->last()?->id === $history->id;
        $afterCancel = $isLatest ? ($active->count() > 1 ? $active[$active->count()-2]->new_salary : $history->old_salary) : $employee->basic_salary;
        $version = $changes->version($employee);
        $audits = SalaryHistoryAudit::where('employee_salary_history_id', $history->id)->latest('id')->get();
        return view('employee.salary-history', compact('employee','history','isLatest','afterCancel','version','audits'));
    }

    public function update(Request $request, Employee $employee, EmployeeSalaryHistory $history, SalaryHistoryChanges $changes)
    {
        abort_unless($history->employee_id === $employee->id, 404);
        $data = $request->validate([
            'confirm_cancel'=>['accepted_if:action,cancel'],
            'action'=>['required','in:correct,cancel'], 'version'=>['required','string','size:64'],
            'amount'=>['required_if:action,correct','nullable','numeric','min:0','max:999999999999.99','decimal:0,2'],
            'entry_reason'=>['required_if:action,correct','nullable','string','max:255'],
            'correction_reason'=>['required','string','max:255'],
        ]);
        $changes->correct($employee, $history, (string) ($data['amount'] ?? 0), $data['entry_reason'] ?? '', $data['correction_reason'], $request->user()->name, $data['version'], $data['action'] === 'cancel');
        return redirect()->route('employee.salary-history.edit', [$employee, $history])->with('success', 'Histori dan gaji pokok sudah diselaraskan. Payroll tersimpan tidak berubah.');
    }
}
