<?php

namespace App\Exports;

use App\Models\Employee;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class EmployeesExport extends DefaultValueBinder implements FromCollection, WithHeadings, WithMapping, WithCustomStartCell, WithStyles, WithColumnFormatting, WithCustomValueBinder, ShouldAutoSize
{
    protected $totalCount = 0;

    /**
     * Memaksa kolom NIK dan Phone menjadi STRING murni di level sel Excel
     */
    public function bindValue(Cell $cell, $value)
    {
        $column = $cell->getColumn();

        // Kolom C (NIK) dan D (Phone) dipaksa sebagai TYPE_STRING murni
        if (in_array($column, ['C', 'D']) && $cell->getRow() > 2) {
            $cell->setValueExplicit((string)$value, DataType::TYPE_STRING);
            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function collection()
    {
        $employees = Employee::with(['site.branch', 'branch'])->get();

        $formattedEmployees = $employees->map(function ($employee) {
            $machineName = strtolower(trim($employee->site->machine_name ?? ''));
            $branchName = strtolower(trim($employee->site->branch->branch_name ?? ''));

            $order = 99;
            $siteLabel = $employee->site ? $employee->site->machine_name : '-';

            if (str_contains($machineName, 'office')) {
                $order = 1;
                $siteLabel = '1_Office/Jakarta';
            } elseif (str_contains($machineName, 'e-beam') || str_contains($machineName, 'ebeam')) {
                $order = 3;
                $siteLabel = '3_E-Beam';
            } elseif (str_contains($machineName, 'ctmic2100')) {
                $order = 4;
                if (str_contains($machineName, 'bali') || str_contains($branchName, 'bali')) {
                    $siteLabel = '4_CTMIC2100-YW/Bali';
                } elseif (str_contains($machineName, 'banyuwangi') || str_contains($branchName, 'banyuwangi')) {
                    $siteLabel = '4_CTMIC2100-YW/Banyuwangi';
                } elseif (str_contains($machineName, 'batam') || str_contains($branchName, 'batam')) {
                    $siteLabel = '4_CTMIC2100-YW/Batam';
                } elseif (str_contains($machineName, 'lampung') || str_contains($branchName, 'lampung')) {
                    $siteLabel = '4_CTMIC2100-YW/Lampung';
                } elseif (str_contains($machineName, 'surabaya') || str_contains($branchName, 'surabaya')) {
                    $siteLabel = '4_CTMIC2100-YW/Surabaya';
                } else {
                    $siteLabel = '4_CTMIC2100-YW';
                }
            } elseif (str_contains($machineName, 'airport') || str_contains($machineName, 'soetta')) {
                $order = 5;
                $siteLabel = '5_Airport SOETTA';
            } elseif (str_contains($machineName, 'fs6000') && (str_contains($machineName, 'jakarta') || str_contains($branchName, 'jakarta'))) {
                $order = 6;
                $siteLabel = '6_FS6000LC/Jakarta';
            } elseif (str_contains($machineName, 'fs6000') && (str_contains($machineName, 'semarang') || str_contains($branchName, 'semarang'))) {
                $order = 7;
                $siteLabel = '7_FS6000LC/Semarang';
            } elseif (str_contains($machineName, 'fs6000') && (str_contains($machineName, 'surabaya') || str_contains($branchName, 'surabaya')) && !str_contains($machineName, 'teluk')) {
                $order = 8;
                $siteLabel = '8_FS6000LC/Surabaya';
            } elseif (str_contains($machineName, 'fs6000') && str_contains($machineName, 'teluk')) {
                $order = 9;
                $siteLabel = '9_FS6000LC/Teluk Lamong';
            }

            $employee->computed_order = $order;
            $employee->computed_site_label = $siteLabel;

            return $employee;
        });

        $sortedEmployees = $formattedEmployees->sort(function ($a, $b) {
            if ($a->computed_order !== $b->computed_order) {
                return $a->computed_order <=> $b->computed_order;
            }

            if ($a->computed_site_label !== $b->computed_site_label) {
                return strcasecmp($a->computed_site_label, $b->computed_site_label);
            }

            return strcasecmp($a->name, $b->name);
        });

        $this->totalCount = $sortedEmployees->count();

        return $sortedEmployees;
    }

    public function startCell(): string
    {
        return 'A1';
    }

    public function headings(): array
    {
        return [
            [
                'Name List of Indonesia Local Staff (Active)',
                'Total: ' . $this->totalCount . ' Person',
                '',
                '',
                '',
                '',
                '',
                '',
                'Today:',
                Carbon::now()->format('Y-m-d'),
                '',
                '',
                '',
                ''
            ],
            [
                'No.',
                'Name',
                'NIK (National ID)',
                'Phone',
                'Email',
                'Position',
                'Work Site',
                'Designation',
                'Join Date',
                'Proba. Finished on',
                'Years of Service',
                'done MCU?',
                'need TLD?',
                'Comment'
            ]
        ];
    }

    public function map($employee): array
    {
        static $no = 0;
        $no++;

        $joinDate = $employee->join_date ? Carbon::parse($employee->join_date) : null;
        $probaFinishedOn = $joinDate ? $joinDate->copy()->addMonths(3)->format('Y-m-d') : '-';

        $yearsOfService = '-';
        if ($joinDate) {
            $diff = $joinDate->diff(Carbon::now());
            $yearsOfService = "{$diff->y} Y / {$diff->m} M / {$diff->d} D";
        }

        $designation = $employee->site->branch->branch_name
            ?? $employee->branch->branch_name
            ?? '-';

        $siteName = $employee->site->machine_name ?? '-';

        return [
            $no,
            $employee->name,
            (string) ($employee->nik ?? '-'),
            (string) ($employee->phone_number ?? '-'),
            $employee->email ?? '-',
            $employee->position ?? '-',
            $siteName,
            $designation,
            $joinDate ? $joinDate->format('Y-m-d') : '-',
            $probaFinishedOn,
            $yearsOfService,
            strtoupper($employee->mcu ?? 'no') === 'YES' ? 'Yes' : 'No',
            strtoupper($employee->tld ?? 'no') === 'YES' ? 'Yes' : 'No',
            ''
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_NUMBER,
            'C' => NumberFormat::FORMAT_TEXT,
            'D' => NumberFormat::FORMAT_TEXT,
            'I' => NumberFormat::FORMAT_DATE_YYYYMMDD,
            'J' => NumberFormat::FORMAT_DATE_YYYYMMDD,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:N1')->getFont()->setBold(true)->setSize(10);
        $sheet->getStyle('A1:N1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->getStyle('A2:N2')->getFont()->setBold(true)->setSize(10);
        $sheet->getStyle('A2:N2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A2:N2')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        $highestRow = $sheet->getHighestRow();
        if ($highestRow >= 2) {
            $sheet->getStyle('A1:N' . $highestRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

            // Alignment
            $sheet->getStyle('A3:A' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('C3:D' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('I3:N' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        return [];
    }
}
