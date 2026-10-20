<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Salary;
use Carbon\Carbon;

class PayrollCalculation
{
    /** Full monthly allowances; amounts are captured when a payroll is created. */
    public function allowancesAt(Employee $employee, string $period): array
    {
        $start = Carbon::createFromFormat('!Y-m', $period);
        if (($employee->join_date && $employee->join_date->gt($start->copy()->endOfMonth()))
            || ($employee->resign_date && $employee->resign_date->lt($start))) return [];

        return $employee->allowances()->where('start_month', '<=', $period)
            ->where(fn ($q) => $q->whereNull('end_month')->orWhere('end_month', '>=', $period))
            ->get()->map(fn ($item) => ['id' => $item->id, 'name' => $item->name, 'amount' => $item->amount])->all();
    }

    public function salaryAt(Employee $employee, Carbon $endOfMonth): float
    {
        $previous = $employee->salaryHistories()->whereNull('cancelled_at')->where('created_at', '<=', $endOfMonth)->reorder()->latest('created_at')->latest('id')->first();
        if ($previous) {
            return (float) $previous->new_salary;
        }
        $next = $employee->salaryHistories()->whereNull('cancelled_at')->where('created_at', '>', $endOfMonth)->reorder()->oldest('created_at')->oldest('id')->first();

        return (float) ($next?->old_salary ?? $employee->basic_salary ?? 0);
    }

    public function calculate($employeeId, $monthPeriod = null, ?float $salaryAmount = null)
    {
        $monthPeriod = $monthPeriod ?? date('Y-m');
        return $this->calculateFor(
            Employee::findOrFail($employeeId),
            \App\Models\Attendance::where('employee_id', $employeeId)->where('month', $monthPeriod)->first(),
            $monthPeriod, $salaryAmount,
            app(IndonesianHolidayService::class)->getHolidaysForMonth($monthPeriod)
        );
    }

    /** Calculate a list/export in a fixed number of queries; agreed amounts stay unchanged. */
    public function calculateMany(iterable $salaries, string $period): array
    {
        $salaries = collect($salaries);
        if ($salaries->isEmpty()) return [];
        $employees = Employee::whereIn('id', $salaries->pluck('employee_id')->unique())->get()->keyBy('id');
        $attendances = \App\Models\Attendance::whereIn('employee_id', $employees->keys())
            ->where('month', $period)->get()->groupBy('employee_id');
        $holidays = app(IndonesianHolidayService::class)->getHolidaysForMonth($period);
        $results = [];
        foreach ($salaries as $salary) {
            $employee = $employees->get($salary->employee_id);
            if (!$employee) $employee = Employee::findOrFail($salary->employee_id);
            $results[$salary->id] = $this->calculateFor($employee,
                $attendances->get($employee->id)?->first(), $period, (float) $salary->amount, $holidays, $salary->allowance_snapshot ?? []);
        }
        return $results;
    }

    private function calculateFor(Employee $employee, ?\App\Models\Attendance $attendance,
        string $monthPeriod, ?float $salaryAmount, array $rawHolidays, array $allowances = []): array
    {
        $startOfMonth = Carbon::parse($monthPeriod.'-01')->startOfMonth();
        $endOfMonth = Carbon::parse($monthPeriod.'-01')->endOfMonth();
        $daysInMonth = $startOfMonth->daysInMonth;

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

        $monthlySalary = $salaryAmount;
        if ($monthlySalary === null) {
            $salaryMaster = Salary::where('employee_id', $employee->id)
                ->where('created_at', '<=', $endOfMonth)->orderByDesc('created_at')->first();
            $monthlySalary = (float) ($salaryMaster?->amount ?? $this->salaryAt($employee, $endOfMonth));
        }

        $dailyRate = $effectiveWorkingDays > 0 ? ($monthlySalary / $effectiveWorkingDays) : 0;

        $baseCalculatedSalary = ($effectiveWorkingDays > 0 && $regularWorkDays < $effectiveWorkingDays)
            ? ($regularWorkDays / $effectiveWorkingDays) * $monthlySalary
            : $monthlySalary;

        $holidayOvertimePay = $holidayOvertimeDays * $dailyRate;
        $allowanceTotal = array_sum(array_map(fn ($item) => (float) $item['amount'], $allowances));
        $totalSalaryToPay = $monthlySalary + $allowanceTotal + $holidayOvertimePay;

        return [
            'monthly_salary' => $monthlySalary,
            'allowances' => $allowances,
            'allowance_total' => $allowanceTotal,
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
