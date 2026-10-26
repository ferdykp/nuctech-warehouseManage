<?php

namespace App\Exports;

use App\Models\Salary;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class SalaryExport extends DefaultValueBinder implements
    FromCollection,
    WithHeadings,
    WithMapping,
    ShouldAutoSize,
    WithStyles,
    WithColumnFormatting,
    WithCustomValueBinder
{
    private int $rowNumber = 0;

    private array $calculations = [];

    public function __construct(
        protected string $month,
        protected int $year,
        protected ?string $search = null,
        protected ?string $information = null,
        protected ?string $bank = null,
        protected ?object $user = null
    ) {}

    /**
     * Nomor rekening harus tetap dalam format teks.
     * Data sekarang dimulai dari baris ketiga.
     */
    public function bindValue(Cell $cell, $value)
    {
        if ($cell->getColumn() === 'E' && $cell->getRow() > 2) {
            $cell->setValueExplicit(
                (string) ($value ?? ''),
                DataType::TYPE_STRING
            );

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    /**
     * Mengambil data penggajian berdasarkan periode
     * dan filter yang dipilih.
     */
    public function collection()
    {
        $this->rowNumber = 0;

        $startDate = Carbon::createFromDate(
            $this->year,
            (int) $this->month,
            1
        )->startOfMonth();

        $endDate = Carbon::createFromDate(
            $this->year,
            (int) $this->month,
            1
        )->endOfMonth();

        $query = Salary::with([
            'employee.site.branch',
            'employee.branch',
        ])->whereBetween('created_at', [
            $startDate->format('Y-m-d H:i:s'),
            $endDate->format('Y-m-d H:i:s'),
        ]);

        // Pembatasan akses berdasarkan site.
        if ($this->user && $this->user->role === 'employee_role') {
            $query->whereHas('employee', function ($q) {
                $q->where('site_id', $this->user->site_id);
            });
        }

        // Filter pencarian.
        if (!empty($this->search)) {
            $search = $this->search;

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('position', 'like', "%{$search}%")
                    ->orWhere('account_no', 'like', "%{$search}%");
            });
        }

        // Filter informasi gaji.
        if (!empty($this->information)) {
            $query->where('information', $this->information);
        }

        // Filter bank.
        if (!empty($this->bank)) {
            $query->where('bank', $this->bank);
        }

        $salaries = $query->get();

        $monthPeriod = sprintf(
            '%04d-%02d',
            $this->year,
            (int) $this->month
        );

        $this->calculations = app(
            \App\Services\PayrollCalculation::class
        )->calculateMany($salaries, $monthPeriod);

        /**
         * Menentukan urutan lokasi/project.
         */
        $formattedSalaries = $salaries->map(function ($salary) {
            $machineName = strtolower(trim(
                $salary->employee->site->machine_name ?? ''
            ));

            $branchName = strtolower(trim(
                $salary->employee->site->branch->branch_name ?? ''
            ));

            $order = 99;

            $siteLabel = $salary->employee->site
                ? $salary->employee->site->machine_name
                : '-';

            if (str_contains($machineName, 'office')) {
                $order = 1;
                $siteLabel = '1_Office/Jakarta';
            } elseif (
                str_contains($machineName, 'e-beam') ||
                str_contains($machineName, 'ebeam')
            ) {
                $order = 3;
                $siteLabel = '3_E-Beam';
            } elseif (str_contains($machineName, 'ctmic2100')) {
                $order = 4;

                if (
                    str_contains($machineName, 'bali') ||
                    str_contains($branchName, 'bali')
                ) {
                    $siteLabel = '4_CTMIC2100-YW/Bali';
                } elseif (
                    str_contains($machineName, 'banyuwangi') ||
                    str_contains($branchName, 'banyuwangi')
                ) {
                    $siteLabel = '4_CTMIC2100-YW/Banyuwangi';
                } elseif (
                    str_contains($machineName, 'batam') ||
                    str_contains($branchName, 'batam')
                ) {
                    $siteLabel = '4_CTMIC2100-YW/Batam';
                } elseif (
                    str_contains($machineName, 'lampung') ||
                    str_contains($branchName, 'lampung')
                ) {
                    $siteLabel = '4_CTMIC2100-YW/Lampung';
                } elseif (
                    str_contains($machineName, 'surabaya') ||
                    str_contains($branchName, 'surabaya')
                ) {
                    $siteLabel = '4_CTMIC2100-YW/Surabaya';
                } else {
                    $siteLabel = '4_CTMIC2100-YW';
                }
            } elseif (
                str_contains($machineName, 'airport') ||
                str_contains($machineName, 'soetta')
            ) {
                $order = 5;
                $siteLabel = '5_Airport SOETTA';
            } elseif (
                str_contains($machineName, 'fs6000') &&
                (
                    str_contains($machineName, 'jakarta') ||
                    str_contains($branchName, 'jakarta')
                )
            ) {
                $order = 6;
                $siteLabel = '6_FS6000LC/Jakarta';
            } elseif (
                str_contains($machineName, 'fs6000') &&
                (
                    str_contains($machineName, 'semarang') ||
                    str_contains($branchName, 'semarang')
                )
            ) {
                $order = 7;
                $siteLabel = '7_FS6000LC/Semarang';
            } elseif (
                str_contains($machineName, 'fs6000') &&
                (
                    str_contains($machineName, 'surabaya') ||
                    str_contains($branchName, 'surabaya')
                ) &&
                !str_contains($machineName, 'teluk')
            ) {
                $order = 8;
                $siteLabel = '8_FS6000LC/Surabaya';
            } elseif (
                str_contains($machineName, 'fs6000') &&
                str_contains($machineName, 'teluk')
            ) {
                $order = 9;
                $siteLabel = '9_FS6000LC/Teluk Lamong';
            }

            $salary->computed_order = $order;
            $salary->computed_site_label = $siteLabel;

            return $salary;
        });

        return $formattedSalaries->sort(function ($a, $b) {
            if ($a->computed_order !== $b->computed_order) {
                return $a->computed_order <=> $b->computed_order;
            }

            if ($a->computed_site_label !== $b->computed_site_label) {
                return strcasecmp(
                    $a->computed_site_label,
                    $b->computed_site_label
                );
            }

            $nameA = $a->name ?? ($a->employee->name ?? '');
            $nameB = $b->name ?? ($b->employee->name ?? '');

            return strcasecmp($nameA, $nameB);
        });
    }

    /**
     * Header Excel:
     * Row 1 : Salary October 2026
     * Row 2 : Nama kolom
     * Row 3+: Data
     */
    public function headings(): array
    {
        $monthName = Carbon::createFromDate(
            $this->year,
            (int) $this->month,
            1
        )->locale('en')->translatedFormat('F');

        $title = "Salary {$monthName} {$this->year}";

        return [
            [
                $title,
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
            ],
            [
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
            ],
        ];
    }

    /**
     * Mapping data salary ke baris Excel.
     */
    public function map($salary): array
    {
        $this->rowNumber++;

        $projectTeamName = $salary->position
            ?? ($salary->employee->position ?? '-');

        $placement = $salary->placement
            ?? $salary->employee->branch->branch_name
            ?? ($salary->employee->site->branch->branch_name ?? '-');

        $numericAmount = (float) ($salary->amount ?? 0)
            + (float) ($salary->allowance_total ?? 0);

        $monthPeriod = sprintf(
            '%04d-%02d',
            $this->year,
            (int) $this->month
        );

        $calc = $this->calculations[$salary->id]
            ?? app(\App\Services\PayrollCalculation::class)
                ->calculateMany(collect([$salary]), $monthPeriod)[$salary->id];

        /**
         * Before/After.
         * Tetap mempertahankan kalkulasi existing.
         */
        if ($calc['holiday_overtime_days'] > 0) {
            $beforeAfter = 'Rp ' .
                number_format($salary->amount, 0, ',', '.') .
                ' / Rp ' .
                number_format($calc['total_salary_to_pay'], 0, ',', '.') .
                ' (+Lembur ' .
                $calc['holiday_overtime_days'] .
                ' Hr Tgl Merah)';
        } elseif (
            !empty($salary->before_after) &&
            $salary->before_after !==
            'Rp ' . number_format($salary->amount ?? 0, 0, ',', '.')
        ) {
            $beforeAfter = $salary->before_after;
        } else {
            $beforeAfter = '';
        }

        /**
         * More Information.
         */
        $employeeJoinDate = $salary->employee->join_date ?? null;

        $infoStatus = strtolower($salary->information ?? '');

        if ($employeeJoinDate) {
            $formattedDate = Carbon::parse(
                $employeeJoinDate
            )->format('Y-m-d');

            if (str_contains($infoStatus, 'probation')) {
                $moreInformation = "Start Join {$formattedDate}";
            } else {
                $moreInformation = "Start Contract {$formattedDate}";
            }
        } else {
            $moreInformation = $salary->more_information
                ?? ($salary->employee->more_information ?? '-');
        }

        /**
         * Get Information.
         */
        $getInformation = !empty($salary->get_information)
            ? $salary->get_information
            : (
                !empty($salary->employee->get_information)
                ? $salary->employee->get_information
                : (
                    !empty($salary->more_information)
                    ? $salary->more_information
                    : ($salary->employee->more_information ?? '-')
                )
            );

        /**
         * Mempertahankan 11 kolom existing.
         */
        return [
            $this->rowNumber,
            $projectTeamName,
            $salary->name,
            $salary->bank,
            (string) $salary->account_no,
            $numericAmount,
            $salary->information ?? '-',
            $salary->allowance_total,
            $moreInformation,
            $placement,
            collect($salary->allowance_snapshot ?? [])
                ->map(
                    fn($item) =>
                    $item['name'] . ' : Rp ' .
                        number_format((float) $item['amount'], 0, ',', '.')
                )
                ->implode('; '),
        ];
    }

    /**
     * Format kolom Excel.
     */
    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_NUMBER,
            'E' => NumberFormat::FORMAT_TEXT,
            'F' => '"Rp "#,##0',
            'H' => '"Rp "#,##0',
        ];
    }

    /**
     * Styling Excel.
     */

    public function styles(Worksheet $sheet)
    {
        $highestRow = max($sheet->getHighestRow(), 2);

        // JUDUL UTAMA - BARIS 1
        $sheet->mergeCells('A1:K1');
        $sheet->getRowDimension(1)->setRowHeight(35);

        $sheet->getStyle('A1:K1')->applyFromArray([
            'font' => [
                'name' => 'Calibri',
                'bold' => true,
                'size' => 16,
                'color' => ['rgb' => '000000'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // HEADER KOLOM - BARIS 2
        $sheet->getRowDimension(2)->setRowHeight(28);

        $sheet->getStyle('A2:K2')->applyFromArray([
            'font' => [
                'name' => 'Calibri',
                'bold' => true,
                'size' => 11,
                'color' => ['rgb' => '000000'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);

        // BORDER TABEL
        // BORDER SELURUH TABEL TERMASUK HEADER
        $sheet->getStyle("A1:K{$highestRow}")
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        // ALIGNMENT DATA
        if ($highestRow >= 3) {
            $sheet->getStyle("A3:K{$highestRow}")
                ->getAlignment()
                ->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->getStyle("A3:A{$highestRow}")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->getStyle("E3:E{$highestRow}")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->getStyle("F3:F{$highestRow}")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $sheet->getStyle("H3:H{$highestRow}")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        // FREEZE HEADER
        $sheet->freezePane('A3');

        // AUTOFILTER
        $sheet->setAutoFilter("A2:K{$highestRow}");

        // PRINT SETTINGS
        $sheet->getPageSetup()
            ->setOrientation(
                \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE
            );

        $sheet->getPageSetup()
            ->setFitToWidth(1)
            ->setFitToHeight(0);

        $sheet->getPageSetup()
            ->setRowsToRepeatAtTopByStartAndEnd(1, 2);

        return [];
    }
}
