<?php

namespace App\Exports\Sheets;

use App\Models\Employee;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AttendanceFix implements FromCollection, WithTitle, WithHeadings
{
    protected $siteId;
    protected $month;

    public function __construct($siteId, $month)
    {
        $this->siteId = $siteId;
        $this->month = $month;
    }

    public function title(): string
    {
        $daftarBulan = [
            '01' => 'Januari',
            '02' => 'Februari',
            '03' => 'Maret',
            '04' => 'April',
            '05' => 'Mei',
            '06' => 'Juni',
            '07' => 'Juli',
            '08' => 'Agustus',
            '09' => 'September',
            '10' => 'Oktober',
            '11' => 'November',
            '12' => 'Desember',
        ];

        $parts = explode('-', $this->month);
        $namaBulan = $daftarBulan[$parts[1]] ?? 'Bulan';

        return $namaBulan . ' ' . $parts[0];
    }

    public function headings(): array
    {
        $carbonMonth = Carbon::parse($this->month . '-01');
        $daysInMonth = $carbonMonth->daysInMonth;

        $headings = ['SN', 'Name'];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $headings[] = $day . '-1';
            $headings[] = $day . '-2';
            $headings[] = $day . '-3';
        }

        return $headings;
    }

    public function collection()
    {
        $startDate = Carbon::parse($this->month . '-01')->startOfMonth()->format('Y-m-d');
        $endDate = Carbon::parse($this->month . '-01')->endOfMonth()->format('Y-m-d');

        // Load relasi attendances DAN schedules
        $employees = Employee::with([
            'attendances' => function ($q) {
                $q->where('month', $this->month);
            },
            'schedules' => function ($q) use ($startDate, $endDate) {
                $q->whereBetween('date', [$startDate, $endDate])->with('shift');
            }
        ])->where('site_id', $this->siteId)->get();

        $collection = collect();
        $sn = 1;

        $carbonMonth = Carbon::parse($this->month . '-01');
        $daysInMonth = $carbonMonth->daysInMonth;

        foreach ($employees as $employee) {
            $attendance = $employee->attendances->first();

            $row = [
                $sn++,
                $employee->name
            ];

            $savedMatrix = [];
            $hasMatrixData = false;
            if ($attendance && !empty($attendance->matrix_details)) {
                $savedMatrix = json_decode($attendance->matrix_details, true);
                $hasMatrixData = true;
            }

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $dateStr = Carbon::parse($this->month . '-' . str_pad($day, 2, '0', STR_PAD_LEFT))->format('Y-m-d');

                if ($hasMatrixData) {
                    $v1 = isset($savedMatrix[$day]['s1']) ? (int) $savedMatrix[$day]['s1'] : 0;
                    $v2 = isset($savedMatrix[$day]['s2']) ? (int) $savedMatrix[$day]['s2'] : 0;
                    $v3 = isset($savedMatrix[$day]['s3']) ? (int) $savedMatrix[$day]['s3'] : 0;
                } else {
                    // FALLBACK KE SCHEDULE JIKA BELUM ADA MATRIX RECORD
                    $sched = $employee->schedules->firstWhere('date', $dateStr);
                    if ($sched && $sched->shift) {
                        $shiftName = strtolower($sched->shift->shift_name ?? '');
                        $isOff = $sched->shift->is_off;

                        if ($isOff) {
                            $v1 = 0;
                            $v2 = 0;
                            $v3 = 0;
                        } elseif (str_contains($shiftName, '2')) {
                            $v1 = 0;
                            $v2 = 1;
                            $v3 = 0;
                        } elseif (str_contains($shiftName, '3')) {
                            $v1 = 0;
                            $v2 = 0;
                            $v3 = 1;
                        } else {
                            $v1 = 1;
                            $v2 = 0;
                            $v3 = 0;
                        }
                    } else {
                        $currentDate = Carbon::parse($dateStr);
                        $v1 = $currentDate->isWeekend() ? 0 : 1;
                        $v2 = 0;
                        $v3 = 0;
                    }
                }

                // Konsisten menggunakan tanda titik (.) untuk penanda hadir
                $row[] = ($v1 === 1) ? 's1' : '';
                $row[] = ($v2 === 1) ? 's2' : '';
                $row[] = ($v3 === 1) ? 's3' : '';
            }

            $collection->push($row);
        }

        return $collection;
    }
}
