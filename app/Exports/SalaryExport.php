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
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class SalaryExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithColumnFormatting
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

        return $query->get()->sort(function ($a, $b) use ($customSiteOrder) {
            $siteIdA = $a->employee->site_id ?? 0;
            $siteIdB = $b->employee->site_id ?? 0;

            $orderA = $customSiteOrder[$siteIdA] ?? 999;
            $orderB = $customSiteOrder[$siteIdB] ?? 999;

            if ($orderA !== $orderB) {
                return $orderA <=> $orderB;
            }

            $nameA = $a->name ?? ($a->employee->name ?? '');
            $nameB = $b->name ?? ($b->employee->name ?? '');

            return strcasecmp($nameA, $nameB);
        });
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

        $projectTeamName = $salary->position ?? ($salary->employee->position ?? '-');
        $placement = $salary->placement
            ?? $salary->employee->branch->branch_name
            ?? ($salary->employee->site->branch->branch_name ?? '-');

        // Nilai nominal murni (float/int) agar bisa di-SUM oleh Excel
        $numericAmount = (float) ($salary->amount ?? 0);

        $monthPeriod = sprintf('%04d-%02d', $this->year, (int)$this->month);
        $holidayService = app(IndonesianHolidayService::class);
        $calc = $this->calculateSalaryDetails($salary->employee_id, $monthPeriod, $holidayService, $salary->amount);

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
            (string) $salary->account_no, // Murni teks
            $numericAmount,              // Angka numerik murni
            $salary->information,
            $beforeAfter,
            $salary->more_information ?? '-',
            $placement,
            $salary->get_information ?? '-',
        ];
    }

    /**
     * Format kolom khusus di Excel
     */
    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_NUMBER,               // No
            'E' => NumberFormat::FORMAT_TEXT,                 // Account No (No Rekening)
            'F' => '"Rp "#,##0',                              // Amount (Rupiah Format Excel)
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();

        // Rata kanan untuk kolom Amount (F)
        if ($highestRow >= 2) {
            $sheet->getStyle("F2:F{$highestRow}")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $sheet->getStyle("A2:A{$highestRow}")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    private function calculateSalaryDetails($employeeId, $monthPeriod, $holidayService, $monthlySalary)
    {
        $startOfMonth = Carbon::parse($monthPeriod . '-01')->startOfMonth();
        $endOfMonth = Carbon::parse($monthPeriod . '-01')->endOfMonth();
        $daysInMonth = $startOfMonth->daysInMonth;

        $rawHolidays = $holidayService->getHolidaysForMonth($monthPeriod);

        $nationalHolidays = [];
        if (!empty($rawHolidays)) {
            foreach ($rawHolidays as $key => $val) {
                $dateKey = ($key instanceof Carbon) ? $key->format('Y-m-d') : (string) $key;
                $nationalHolidays[$dateKey] = $val;
            }
        }

        $effectiveWorkingDays = 0;
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date = Carbon::parse(sprintf('%s-%02d', $monthPeriod, $d));
            $dateStr = $date->format('Y-m-d');

            if (!$date->isWeekend() && !isset($nationalHolidays[$dateStr])) {
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
