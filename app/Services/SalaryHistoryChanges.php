<?php

namespace App\Services;

use App\Models\{Employee, EmployeeSalaryHistory, SalaryHistoryAudit};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalaryHistoryChanges
{
    public function version(Employee $employee): string
    {
        return hash('sha256', json_encode([$employee->basic_salary, $employee->salaryHistories()->reorder()->orderBy('created_at')->orderBy('id')->get()->toArray()]));
    }

    public function correct(Employee $employee, EmployeeSalaryHistory $history, string $amount, string $entryReason, string $reason, string $actor, ?string $version = null, bool $cancel = false): void
    {
        DB::transaction(function () use ($employee, $history, $amount, $entryReason, $reason, $actor, $version, $cancel) {
            $employee = Employee::lockForUpdate()->findOrFail($employee->id);
            $rows = $employee->salaryHistories()->reorder()->orderBy('created_at')->orderBy('id')->lockForUpdate()->get();
            $target = $rows->firstWhere('id', $history->id);
            abort_unless($target, 404);
            if ($version !== null && !hash_equals($this->version($employee), $version)) {
                throw ValidationException::withMessages(['version' => 'Data gaji telah berubah. Muat ulang halaman untuk meninjau nominal terbaru.']);
            }
            if ($target->cancelled_at) throw ValidationException::withMessages(['history' => 'Catatan ini sudah dibatalkan.']);
            $before = ['basic_salary' => $employee->basic_salary, 'histories' => $rows->toArray()];
            $active = $rows->whereNull('cancelled_at');
            $baseline = $active->first()->old_salary;
            if ($cancel) $target->cancelled_at = now();
            else {
                $target->new_salary = $amount;
                $target->reason = $entryReason;
            }
            $target->save();
            $running = $baseline;
            foreach ($rows as $row) {
                if ($row->cancelled_at) continue;
                $row->old_salary = $running;
                $row->save();
                $running = $row->new_salary;
            }
            $employee->update(['basic_salary' => $running]);
            SalaryHistoryAudit::create([
                'employee_id' => $employee->id, 'employee_salary_history_id' => $target->id,
                'action' => $cancel ? 'cancelled' : 'corrected', 'reason' => $reason, 'actor' => $actor,
                'before' => $before, 'after' => ['basic_salary' => $running, 'histories' => $rows->toArray()],
            ]);
        });
    }

    public function record(Employee $employee, string $amount, string $mode, ?string $reason, string $actor): void
    {
        if ((float) $employee->basic_salary === (float) $amount) return;
        if ($mode === 'correction') {
            if (!filled($reason)) throw ValidationException::withMessages(['salary_change_reason' => 'Alasan koreksi salah input wajib diisi.']);
            $latest = $employee->salaryHistories()->whereNull('cancelled_at')->reorder()->latest('created_at')->latest('id')->first();
            if ($latest) {
                $this->correct($employee, $latest, $amount, $latest->reason ?? 'Gaji pokok', $reason, $actor);
                return;
            }
        }
        $history = $employee->salaryHistories()->create([
            'old_salary' => $mode === 'correction' ? 0 : ($employee->basic_salary ?? 0),
            'new_salary' => $amount, 'reason' => $reason ?: 'Perubahan gaji pokok', 'updated_by' => $actor,
        ]);
        if ($mode === 'correction') SalaryHistoryAudit::create([
            'employee_id'=>$employee->id, 'employee_salary_history_id'=>$history->id, 'action'=>'corrected',
            'reason'=>$reason, 'actor'=>$actor, 'before'=>['basic_salary'=>$employee->basic_salary,'histories'=>[]],
            'after'=>['basic_salary'=>$amount,'histories'=>[$history->toArray()]],
        ]);
    }
}
