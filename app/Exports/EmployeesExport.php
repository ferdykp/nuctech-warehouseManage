<?php

namespace App\Exports;

use App\Exports\Sheets\EmployeeListSheet;
use App\Models\Employee;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class EmployeesExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        [$resigned, $active] = Employee::with(['site.branch', 'branch'])->get()
            ->partition(fn ($employee) => strtolower(trim($employee->status ?? '')) === 'resigned'
                || ($employee->is_active !== null && ! (bool) $employee->is_active)
                || ($employee->resign_date && $employee->resign_date->lte(today())));

        return [
            new EmployeeListSheet($active),
            new EmployeeListSheet($resigned, true),
        ];
    }
}
