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
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class EmployeesExport implements FromCollection, WithHeadings, WithMapping, WithCustomStartCell, WithStyles, WithColumnFormatting, ShouldAutoSize
{
    protected $totalCount = 0;

    public function collection()
    {
        $customSiteOrder = [
            // id_site => urutan
            5 => 1, // Site office (ID 5 di DB) dipaksa urutan ke-3
            12 => 2,
            15 => 3,
            16 => 4,
            8 => 5,
            7 => 6,
            6 => 7,
            13 => 8,
            2 => 9,
            1 => 10,
            6 => 11,
            4 => 12
            // site_id lainnya akan otomatis ditempatkan di akhir (default 999)
        ];

        $employees = Employee::with(['site.branch', 'branch'])->get();

        $sortedEmployees = $employees->sort(function ($a, $b) use ($customSiteOrder) {
            $orderA = $customSiteOrder[$a->site_id] ?? 999;
            $orderB = $customSiteOrder[$b->site_id] ?? 999;

            if ($orderA !== $orderB) {
                return $orderA <=> $orderB;
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
            (string) ($employee->nik ?? '-'),          // Menggunakan string murni tanpa "'"
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
            'C' => NumberFormat::FORMAT_TEXT, // NIK selalu dibaca Teks murni
            'D' => NumberFormat::FORMAT_TEXT, // Nomor HP dibaca Teks murni
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

            $sheet->getStyle('A3:A' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('C3:C' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('I3:N' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        return [];
    }
}
