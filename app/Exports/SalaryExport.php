<?php

namespace App\Exports;

use App\Models\Salary;
use App\Services\IndonesianHolidayService;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalaryExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    private $rowNumber = 0;

    public function __construct(
        protected string $month,
        protected int $year,
        protected ?string $search = null,
        protected ?string $information = null,
        protected ?string $bank = null,
        protected ?object $user = null
    ) {}

    public function collection()
    {
        $startDate = Carbon::createFromDate($this->year, (int) $this->month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($this->year, (int) $this->month, 1)->endOfMonth();

        $query = Salary::with(['employee.site.branch', 'employee.branch'])
            ->whereBetween('created_at', [
                $startDate->format('Y-m-d H:i:s'),
                $endDate->format('Y-m-d H:i:s'),
            ]);

        if ($this->user && $this->user->role === 'employee_role') {
            $query->whereHas('employee', function ($q) {
                $q->where('site_id', $this->user->site_id);
            });
        }

        if (!empty($this->search)) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('position', 'like', "%{$search}%")
                    ->orWhere('account_no', 'like', "%{$search}%");
            });
        }

        if (!empty($this->information)) {
            $query->where('information', $this->information);
        }

        if (!empty($this->bank)) {
            $query->where('bank', $this->bank);
        }

        // 1. Tentukan urutan id_site persis sama dengan AttendanceDetailSheet
        $customSiteOrder = [
            1 => 7,
            2 => 6,
            3 => 8,
            4 => 9,
            5 => 1,
            7 => 4,
            8 => 4,
            9 => 4,
            13 => 3,
            14 => 5,
        ];

        // 2. Ambil data & urutkan berdasarkan customSiteOrder dan Nama Karyawan
        $salaries = $query->get()->sort(function ($a, $b) use ($customSiteOrder) {
            $siteIdA = $a->employee->site_id ?? 0;
            $siteIdB = $b->employee->site_id ?? 0;

            $orderA = $customSiteOrder[$siteIdA] ?? 999;
            $orderB = $customSiteOrder[$siteIdB] ?? 999;

            // Bandingkan berdasarkan urutan Site
            if ($orderA !== $orderB) {
                return $orderA <=> $orderB;
            }

            // Jika Site sama, urutkan berdasarkan Nama Karyawan (Alfabet)
            $nameA = $a->name ?? ($a->employee->name ?? '');
            $nameB = $b->name ?? ($b->employee->name ?? '');

            return strcasecmp($nameA, $nameB);
        });

        return $salaries;
    }

    public function headings(): array
    {
        return [
            'No',
            'Project Team Name',
            'Name',
            'Bank',
            'Account No.',
            'Amount',
            'Information',
            'Before/After',
            'More Information',
            'Placement',
            'Get Information',
        ];
    }

    public function map($salary): array
    {
        $this->rowNumber++;

        // Project Team Name isinya posisi
        $projectTeamName = $salary->position ?? ($salary->employee->position ?? '-');

        // Placement isinya branch
        $placement = $salary->placement
            ?? $salary->employee->branch->branch_name
            ?? ($salary->employee->site->branch->branch_name ?? '-');

        /*
        |--------------------------------------------------------------------------
        | Format Nominal Rp untuk Kolom Amount
        |--------------------------------------------------------------------------
        */
        $formattedAmount = 'Rp ' . number_format($salary->amount ?? 0, 0, ',', '.');

        /*
        |--------------------------------------------------------------------------
        | Hitung Ulang Kalkulasi Lembur Tanggal Merah untuk Kolom Before/After
        |--------------------------------------------------------------------------
        */
        $monthPeriod = sprintf('%04d-%02d', $this->year, (int)$this->month);
        $holidayService = app(IndonesianHolidayService::class);

        $calc = $this->calculateSalaryDetails($salary->employee_id, $monthPeriod, $holidayService, $salary->amount);

        // Jika ada lemburan tanggal merah, tampilkan "Gaji Pokok / Total Gaji"
        if ($calc['holiday_overtime_days'] > 0) {
            $beforeAfter = 'Rp ' . number_format($salary->amount, 0, ',', '.') .
                ' / Rp ' . number_format($calc['total_salary_to_pay'], 0, ',', '.') .
                ' (+Lembur ' . $calc['holiday_overtime_days'] . ' Hr Tgl Merah)';
        } else {
            $beforeAfter = $salary->before_after ?? 'Rp ' . number_format($salary->amount, 0, ',', '.');
        }

        return [
            $this->rowNumber,
            $projectTeamName,
            $salary->name,
            $salary->bank,
            $salary->account_no, // No. Rekening
            $formattedAmount, // Format Rp
            $salary->information,
            $beforeAfter,
            $salary->more_information ?? '-',
            $placement,
            $salary->get_information ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Bold header baris pertama
            1 => ['font' => ['bold' => true]],
        ];
    }

    /**
     * Method pembantu hitung kalkulasi lembur khusus export
     */
    private function calculateSalaryDetails($employeeId, $monthPeriod, $holidayService, $monthlySalary)
    {
        $startOfMonth = Carbon::parse($monthPeriod . '-01')->startOfMonth();
        $endOfMonth = Carbon::parse($monthPeriod . '-01')->endOfMonth();
        $daysInMonth = $startOfMonth->daysInMonth;

        $rawHolidays = $holidayService->getHolidaysForMonth($monthPeriod);

        // Standardisasi key array agar selalu berupa string "Y-m-d"
        $nationalHolidays = [];
        if (!empty($rawHolidays)) {
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

            if (!$isWeekend && !$isNationalHoliday) {
                $effectiveWorkingDays++;
            }
        }

        $schedules = \App\Models\EmployeeSchedule::where('employee_id', $employeeId)
            ->whereBetween('date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
            ->with('shift')
            ->get();

        $holidayOvertimeDays = 0;

        foreach ($schedules as $sched) {
            if ($sched->shift && !$sched->shift->is_off) {
                // Pastikan $dateStr berupa string murni
                $dateStr = $sched->date instanceof Carbon
                    ? $sched->date->format('Y-m-d')
                    : (string) $sched->date;

                if (isset($nationalHolidays[$dateStr])) {
                    $holidayOvertimeDays++;
                }
            }
        }

        $dailyRate = $effectiveWorkingDays > 0 ? ($monthlySalary / $effectiveWorkingDays) : 0;
        $holidayOvertimePay = $holidayOvertimeDays * $dailyRate;
        $totalSalaryToPay = $monthlySalary + $holidayOvertimePay;

        return [
            'holiday_overtime_days' => $holidayOvertimeDays,
            'total_salary_to_pay'   => $totalSalaryToPay,
        ];
    }
}
