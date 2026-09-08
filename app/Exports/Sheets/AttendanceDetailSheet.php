<?php

namespace App\Exports\Sheets;

use App\Models\Employee;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class AttendanceDetailSheet implements FromCollection, WithTitle, WithHeadings, WithColumnWidths, WithStyles, WithEvents
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

        return $namaBulan . ' ' . $parts[0] . ' dengan site';
    }

    public function headings(): array
    {
        $carbonMonth = Carbon::parse($this->month . '-01');
        $daysInMonth = $carbonMonth->daysInMonth;

        $headings = ['SN', 'Site', 'Name'];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $headings[] = $day . '-1';
            $headings[] = $day . '-2';
            $headings[] = $day . '-3';
        }

        return $headings;
    }

    public function columnWidths(): array
    {
        $carbonMonth = Carbon::parse($this->month . '-01');
        $daysInMonth = $carbonMonth->daysInMonth;

        $widths = [
            'A' => 5.7,   // SN
            'B' => 32.0,  // Site
            'C' => 30.7,  // Name
        ];

        $colCount = 3 + ($daysInMonth * 3);
        for ($i = 4; $i <= $colCount; $i++) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
            $widths[$colLetter] = 4.3;
        }

        return $widths;
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $fullRange = "A1:{$highestColumn}{$highestRow}";

        return [
            $fullRange => [
                'font' => [
                    'name' => 'Calibri',
                    'size' => 11,
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => '000000'],
                    ],
                ],
            ],
            "B2:C{$highestRow}" => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
            ],
            "1" => [
                'font' => [
                    'bold' => true,
                ],
            ]
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
                $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);

                $sheet->getPageMargins()->setTop(0.59);
                $sheet->getPageMargins()->setBottom(1.57);
                $sheet->getPageMargins()->setLeft(0.24);
                $sheet->getPageMargins()->setRight(0.24);
                $sheet->getPageMargins()->setHeader(0.31);
                $sheet->getPageMargins()->setFooter(0.31);

                $sheet->setPageSetup($sheet->getPageSetup()->setColumnsToRepeatAtLeftByStartAndEnd('A', 'C'));
                $sheet->setPageSetup($sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1));

                $highestRow = $sheet->getHighestRow();
                for ($row = 31; $row < $highestRow; $row += 30) {
                    $sheet->setBreak("A{$row}", Worksheet::BREAK_ROW);
                }

                $sheet->getHeaderFooter()->setOddHeader('&C&"Calibri,Bold"Attendance List &A');

                $noteText = "Note:\n1-1: shift1, from 8:00 to 16:00;\n1-2: shift2, from 16:00 to 0:00;\n1-3: shift3, from 0:00 to 8:00;";
                $sheet->getHeaderFooter()->setOddFooter(
                    "&L{$noteText}" .
                        "&C\n\n\n\n\n&P of &N" .
                        "&RConfirmed By: _____________________________________"
                );
            },
        ];
    }

    public function collection()
    {
        $startDate = Carbon::parse($this->month . '-01')->startOfMonth()->format('Y-m-d');
        $endDate = Carbon::parse($this->month . '-01')->endOfMonth()->format('Y-m-d');

        $employeesQuery = Employee::with([
            'site.branch',
            'attendances' => function ($q) {
                $q->where('month', $this->month);
            },
            'schedules' => function ($q) use ($startDate, $endDate) {
                $q->whereBetween('date', [$startDate, $endDate])->with('shift');
            }
        ]);

        if (!empty($this->siteId) && $this->siteId !== 'all') {
            $employeesQuery->where('site_id', $this->siteId);
        }

        $employees = $employeesQuery->get();

        // LOGIKA PENENTUAN URUTAN & FORMAT NAMA SITE SESUAI TARGET GAMBAR 2
        $formattedEmployees = $employees->map(function ($employee) {
            $machineName = strtolower(trim($employee->site->machine_name ?? ''));
            $branchName = strtolower(trim($employee->site->branch->branch_name ?? ''));

            // Default
            $order = 99;
            $siteLabel = $employee->site ? $employee->site->machine_name : '-';

            // 1_Office/Jakarta
            if (str_contains($machineName, 'office')) {
                $order = 1;
                $siteLabel = '1_Office/Jakarta';
            }
            // 3_E-Beam
            elseif (str_contains($machineName, 'e-beam') || str_contains($machineName, 'ebeam')) {
                $order = 3;
                $siteLabel = '3_E-Beam';
            }
            // 4_CTMIC2100-YW/[Lokasi]
            elseif (str_contains($machineName, 'ctmic2100')) {
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
            }
            // 5_Airport SOETTA
            elseif (str_contains($machineName, 'airport') || str_contains($machineName, 'soetta')) {
                $order = 5;
                $siteLabel = '5_Airport SOETTA';
            }
            // 6_FS6000LC/Jakarta
            elseif (str_contains($machineName, 'fs6000') && (str_contains($machineName, 'jakarta') || str_contains($branchName, 'jakarta'))) {
                $order = 6;
                $siteLabel = '6_FS6000LC/Jakarta';
            }
            // 7_FS6000LC/Semarang
            elseif (str_contains($machineName, 'fs6000') && (str_contains($machineName, 'semarang') || str_contains($branchName, 'semarang'))) {
                $order = 7;
                $siteLabel = '7_FS6000LC/Semarang';
            }
            // 8_FS6000LC/Surabaya
            elseif (str_contains($machineName, 'fs6000') && (str_contains($machineName, 'surabaya') || str_contains($branchName, 'surabaya')) && !str_contains($machineName, 'teluk')) {
                $order = 8;
                $siteLabel = '8_FS6000LC/Surabaya';
            }
            // 9_FS6000LC/Teluk Lamong
            elseif (str_contains($machineName, 'fs6000') && str_contains($machineName, 'teluk')) {
                $order = 9;
                $siteLabel = '9_FS6000LC/Teluk Lamong';
            }

            $employee->computed_order = $order;
            $employee->computed_site_label = $siteLabel;

            return $employee;
        });

        // PENGURUTAN MULTI-LEVEL YANG DIJAMIN PRESISI
        $sortedEmployees = $formattedEmployees->sort(function ($a, $b) {
            // Level 1: Urutkan berdasar Angka Order (1, 3, 4, 5, 6, 7, 8, 9)
            if ($a->computed_order !== $b->computed_order) {
                return $a->computed_order <=> $b->computed_order;
            }

            // Level 2: Urutkan berdasar Nama Site Label (misal di grup 4: Bali, Banyuwangi, Batam...)
            if ($a->computed_site_label !== $b->computed_site_label) {
                return strcasecmp($a->computed_site_label, $b->computed_site_label);
            }

            // Level 3: Urutkan berdasar Nama Karyawan (A-Z)
            return strcasecmp($a->name, $b->name);
        });

        $collection = collect();
        $sn = 1;

        $carbonMonth = Carbon::parse($this->month . '-01');
        $daysInMonth = $carbonMonth->daysInMonth;

        foreach ($sortedEmployees as $employee) {
            $attendance = $employee->attendances->first();

            $row = [
                $sn++,
                $employee->computed_site_label,
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

                $row[] = ($v1 === 1) ? '.' : '';
                $row[] = ($v2 === 1) ? '.' : '';
                $row[] = ($v3 === 1) ? '.' : '';
            }

            $collection->push($row);
        }

        return $collection;
    }
}
