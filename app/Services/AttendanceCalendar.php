<?php

namespace App\Services;

use App\Models\Employee;
use Carbon\Carbon;

class AttendanceCalendar
{
    public function matrix(Employee $employee, string $month, ?array $saved = null): array
    {
        $employee->loadMissing('schedules.shift');
        $schedules = $employee->schedules->keyBy(fn ($schedule) => $schedule->date->format('Y-m-d'));
        $start = Carbon::createFromFormat('!Y-m', $month);
        $matrix = [];

        for ($day = 1; $day <= $start->daysInMonth; $day++) {
            $date = $start->copy()->day($day);
            $sessions = ['s1' => 0, 's2' => 0, 's3' => 0];
            if (!$employee->resign_date || $date->lte($employee->resign_date)) {
                if (isset($saved[$day])) {
                    foreach ($sessions as $key => $value) {
                        $sessions[$key] = (int) (($saved[$day][$key] ?? 0) == 1);
                    }
                } elseif ($saved === null) {
                    $shift = $schedules->get($date->format('Y-m-d'))?->shift;
                    if ($shift && !$shift->is_off) {
                        $name = strtolower($shift->shift_name);
                        $key = str_contains($name, '2') ? 's2' : (str_contains($name, '3') ? 's3' : 's1');
                        $sessions[$key] = 1;
                    }
                }
            }
            $matrix[$day] = $sessions;
        }

        return $matrix;
    }

    public function count(array $matrix): int
    {
        return array_sum(array_map('array_sum', $matrix));
    }
}
