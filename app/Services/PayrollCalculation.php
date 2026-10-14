<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Salary;
use Carbon\Carbon;

class PayrollCalculation
{
    public function salaryAt(Employee $employee, Carbon $endOfMonth): float
    {
        $previous = $employee->salaryHistories()->where('created_at', '<=', $endOfMonth)->reorder()->latest('created_at')->latest('id')->first();
        if ($previous) {
            return (float) $previous->new_salary;
        }
        $next = $employee->salaryHistories()->where('created_at', '>', $endOfMonth)->reorder()->oldest('created_at')->oldest('id')->first();

        return (float) ($next?->old_salary ?? $employee->basic_salary ?? 0);
    }

    public function calculate($employeeId, $monthPeriod = null, ?float $salaryAmount = null)
    {
        $monthPeriod = $monthPeriod ?? date('Y-m');

        $startOfMonth = Carbon::parse($monthPeriod.'-01')->startOfMonth();
        $endOfMonth = Carbon::parse($monthPeriod.'-01')->endOfMonth();
        $daysInMonth = $startOfMonth->daysInMonth;

        $rawHolidays = app(IndonesianHolidayService::class)->getHolidaysForMonth($monthPeriod);

        $nationalHolidays = [];
        if (! empty($rawHolidays)) {
            foreach ($rawHolidays as $key => $val) {
                if ($key instanceof Carbon) {
                    $dateKey = $key->format('Y-m-d');
                } else {
                    $dateKey = (string) $key;
                }
                $nationalHolidays[$dateKey] = $val;
            }
        }

        $effectiveWorkingDays = 0;
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date = Carbon::parse(sprintf('%s-%02d', $monthPeriod, $d));
            $dateStr = $date->format('Y-m-d');

            $isWeekend = $date->isWeekend();
            $isNationalHoliday = isset($nationalHolidays[$dateStr]);

            if (! $isWeekend && ! $isNationalHoliday) {
                $effectiveWorkingDays++;
            }
        }

        $regularWorkDays = 0;
        $holidayOvertimeDays = 0;
        $holidayDates = [];

        $employee = Employee::findOrFail($employeeId);
        $attendance = \App\Models\Attendance::where('employee_id', $employeeId)->where('month', $monthPeriod)->first();
        $matrix = json_decode($attendance?->matrix_details ?? '{}', true) ?? [];
        foreach ($matrix as $day => $sessions) {
            if (! ctype_digit((string) $day) || $day < 1 || $day > $daysInMonth || ! is_array($sessions)) {
                continue;
            }
            $date = $startOfMonth->copy()->day((int) $day);
            if (($employee->join_date && $date->lt($employee->join_date)) || ($employee->resign_date && $date->gt($employee->resign_date))) {
                continue;
            }
            if (array_sum(array_intersect_key($sessions, array_flip(['s1', 's2', 's3']))) <= 0) {
                continue;
            }
            $dateStr = $date->toDateString();
            if (isset($nationalHolidays[$dateStr])) {
                $holidayOvertimeDays++;
                $holidayDates[] = ['date' => $dateStr, 'name' => $nationalHolidays[$dateStr]];
            } else {
                $regularWorkDays++;
            }
        }

        $salaryMaster = Salary::where('employee_id', $employeeId)
            ->where('created_at', '<=', $endOfMonth)
            ->orderByDesc('created_at')
            ->first();

        $monthlySalary = $salaryAmount ?? (float) ($salaryMaster?->amount ?? $this->salaryAt(Employee::findOrFail($employeeId), $endOfMonth));

        $dailyRate = $effectiveWorkingDays > 0 ? ($monthlySalary / $effectiveWorkingDays) : 0;

        $baseCalculatedSalary = ($effectiveWorkingDays > 0 && $regularWorkDays < $effectiveWorkingDays)
            ? ($regularWorkDays / $effectiveWorkingDays) * $monthlySalary
            : $monthlySalary;

        $holidayOvertimePay = $holidayOvertimeDays * $dailyRate;
        $totalSalaryToPay = $monthlySalary + $holidayOvertimePay;

        return [
            'monthly_salary' => $monthlySalary,
            'effective_working_days' => $effectiveWorkingDays,
            'total_attended_days' => $regularWorkDays + $holidayOvertimeDays,
            'regular_work_days' => $regularWorkDays,
            'holiday_overtime_days' => $holidayOvertimeDays,
            'holiday_dates' => $holidayDates,
            'daily_rate' => $dailyRate,
            'base_calculated_salary' => $baseCalculatedSalary,
            'holiday_overtime_pay' => $holidayOvertimePay,
            'total_salary_to_pay' => $totalSalaryToPay,
        ];
    }
}
