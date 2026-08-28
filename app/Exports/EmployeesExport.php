<?php

namespace App\Exports;

use App\Models\Employee;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class EmployeesExport implements FromCollection, WithHeadings, WithMapping, WithCustomStartCell, WithStyles, ShouldAutoSize
{
    protected $totalCount = 0;

    public function collection()
    {
        // Mengambil SELURUH data karyawan aktif/terdaftar tanpa filter pencarian atau pagination
        $employees = Employee::with(['site.branch', 'branch'])->latest()->get();

        $this->totalCount = $employees->count();

        return $employees;
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

        // 1. Proba. Finished on = Join Date + 3 Bulan
        $probaFinishedOn = $joinDate ? $joinDate->copy()->addMonths(3)->format('Y-m-d') : '-';

        // 2. Years of Service Format (Presisi seperti format Excel: "1 Y / 3 M / 23 D")
        $yearsOfService = '-';
        if ($joinDate) {
            $diff = $joinDate->diff(Carbon::now());
            $yearsOfService = "{$diff->y} Y / {$diff->m} M / {$diff->d} D";
        }

        // 3. Designation = Branch Name
        $designation = $employee->site->branch->branch_name
            ?? $employee->branch->branch_name
            ?? '-';

        // 4. Site Location Name
        $siteName = $employee->site->machine_name ?? '-';

        return [
            $no,
            $employee->name,
            $employee->nik ? "'" . $employee->nik : '-', // Dipetik agar NIK format teks murni di Excel
            $employee->phone_number ?? '-',
            $employee->email ?? '-',
            $employee->position ?? '-',
            $siteName,
            $designation,
            $joinDate ? $joinDate->format('Y-m-d') : '-',
            $probaFinishedOn,
            $yearsOfService,
            strtoupper($employee->mcu ?? 'no') === 'YES' ? 'Yes' : 'No',
            strtoupper($employee->tld ?? 'no') === 'YES' ? 'Yes' : 'No',
            '' // Comment kosong untuk diisi manual
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Styling Top Bar (Row 1)
        $sheet->getStyle('A1:N1')->getFont()->setBold(true)->setSize(10);
        $sheet->getStyle('A1:N1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        // Styling Table Header (Row 2)
        $sheet->getStyle('A2:N2')->getFont()->setBold(true)->setSize(10);
        $sheet->getStyle('A2:N2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A2:N2')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        // Border tipis hitam untuk seluruh tabel
        $highestRow = $sheet->getHighestRow();
        if ($highestRow >= 2) {
            $sheet->getStyle('A1:N' . $highestRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

            // Alignment Tengah untuk Kolom No, NIK, Join Date, Proba, Years of Service, MCU, TLD
            $sheet->getStyle('A3:A' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('C3:C' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('I3:N' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        return [];
    }
}
