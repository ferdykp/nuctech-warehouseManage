<?php

namespace App\Exports;

use App\Models\Employee;
use App\Models\Shift;
use App\Models\Site;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ScheduleExport implements FromCollection, WithTitle, WithStyles, WithDrawings
{
    protected $siteId;
    protected $month;
    protected $year;

    public function __construct($siteId, $month, $year)
    {
        $this->siteId = $siteId;
        $this->month = sprintf('%02d', $month);
        $this->year = $year;
    }

    public function title(): string
    {
        return date('F \'y', mktime(0, 0, 0, (int)$this->month, 1, $this->year));
    }

    public function drawings()
    {
        $drawing = new Drawing();
        $drawing->setName('Nuctech Logo');
        $drawing->setDescription('Nuctech Company Logo');

        $imagePath = public_path('img/nuctech-logo.png');
        if (file_exists($imagePath)) {
            $drawing->setPath($imagePath);
        }

        $drawing->setHeight(65);
        $drawing->setCoordinates('B1');
        $drawing->setOffsetX(5);
        $drawing->setOffsetY(10);

        return $drawing;
    }

    public function collection()
    {
        $startDate = Carbon::createFromDate($this->year, $this->month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($this->year, $this->month, 1)->endOfMonth();
        $totalDays = $startDate->daysInMonth;

        $siteName = 'ALL SITES';
        $subTitle = 'ALL SITES - ALL LOCATIONS';

        if ($this->siteId !== 'all' && !empty($this->siteId)) {
            $site = Site::find($this->siteId);
            if ($site) {
                $siteName = $site->machine_name;
                $locVal = trim((string) $site->location);

                if (!empty($locVal) && $locVal !== '--' && $locVal !== '-') {
                    $subTitle = strtoupper($site->machine_name . ' - ' . $locVal);
                } else {
                    $subTitle = strtoupper($site->machine_name);
                }
            }
        }

        $employeesQuery = Employee::with(['schedules' => function ($q) use ($startDate, $endDate) {
            $q->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->with('shift');
        }]);

        if ($this->siteId !== 'all' && !empty($this->siteId)) {
            $employeesQuery->where('site_id', $this->siteId);
        }

        $employees = $employeesQuery->get();
        $rows = collect();

        // BARIS 1-5
        $rows->push(['', '', 'WORK SCHEDULE ENGINEERS - NUCTECH COMPANY LIMITED INDONESIA']);
        $rows->push(['', '', $subTitle]);
        $rows->push(['', '']);
        $rows->push(['', '']);
        $rows->push(['', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 'Working Site: ' . $siteName]);

        // BLOK 1: Tanggal 1 s/d 16
        $monthName = Carbon::createFromDate($this->year, $this->month, 1)->format('F  Y');

        $rowMonth1 = ['', 'NAME', 'MONTH', $monthName];
        $rowDay1   = ['', '', 'DAY'];
        $rowDate1  = ['', '', 'DATE'];

        for ($d = 1; $d <= 16; $d++) {
            $date = Carbon::createFromDate($this->year, $this->month, $d);
            $rowDay1[]  = $date->translatedFormat('D');
            $rowDate1[] = $d;
        }

        $rows->push($rowMonth1);
        $rows->push($rowDay1);
        $rows->push($rowDate1);

        $no = 1;
        foreach ($employees as $emp) {
            // Mapping jadwal berdasarkan string Y-m-d agar akurat
            $schedulesMap = $emp->schedules->keyBy(function ($item) {
                return $item->date instanceof Carbon ? $item->date->format('Y-m-d') : (string)$item->date;
            });

            $empRow = [$no, '', $emp->name];
            for ($d = 1; $d <= 16; $d++) {
                $dateStr = Carbon::createFromDate($this->year, $this->month, $d)->format('Y-m-d');
                $sched = $schedulesMap->get($dateStr);
                $empRow[] = $this->getShiftCode($sched);
            }
            $rows->push($empRow);
            $no++;
        }

        $rows->push(['', '']);
        $rows->push(['', '']);

        // BLOK 2: Tanggal 17 s/d Akhir Bulan
        $rowMonth2 = ['', 'NAME', 'MONTH', $monthName];
        $rowDay2   = ['', '', 'DAY'];
        $rowDate2  = ['', '', 'DATE'];

        for ($d = 17; $d <= $totalDays; $d++) {
            $date = Carbon::createFromDate($this->year, $this->month, $d);
            $rowDay2[]  = $date->translatedFormat('D');
            $rowDate2[] = $d;
        }

        $rows->push($rowMonth2);
        $rows->push($rowDay2);
        $rows->push($rowDate2);

        $no = 1;
        foreach ($employees as $emp) {
            $schedulesMap = $emp->schedules->keyBy(function ($item) {
                return $item->date instanceof Carbon ? $item->date->format('Y-m-d') : (string)$item->date;
            });

            $empRow = [$no, '', $emp->name];
            for ($d = 17; $d <= $totalDays; $d++) {
                $dateStr = Carbon::createFromDate($this->year, $this->month, $d)->format('Y-m-d');
                $sched = $schedulesMap->get($dateStr);
                $empRow[] = $this->getShiftCode($sched);
            }
            $rows->push($empRow);
            $no++;
        }

        $rows->push(['', '']);

        // COMMENT: SHIFT DINAMIS DARI DB
        $rows->push(['', 'Comment:']);
        $shifts = Shift::orderBy('is_off', 'asc')->orderBy('start_time', 'asc')->get();

        foreach ($shifts as $sf) {
            if ($sf->is_off) {
                $rows->push(['', '', 'L', 'Off']);
            } else {
                $code = $this->extractShiftCode($sf->shift_name);
                $timeText = Carbon::parse($sf->start_time)->format('H.i') . ' - ' . Carbon::parse($sf->end_time)->format('H.i');
                $rows->push(['', '', $code, $timeText]);
            }
        }

        $rows->push(['', '']);

        // NOTE
        $rows->push(['', 'Note:', 'If you have to take a leave, you must adjust your duty with some other person. There shall be no gap in duty.']);
        $rows->push(['', '', 'If you want to take a leave, you must ask the permission from your site team leader at least 8 hours before your duty time.']);

        $rows->push(['', '']);
        $rows->push(['', '']);
        $rows->push(['', '']);

        // TABEL SUMMARY BAWAH
        $rows->push(['', 'Name', 'Phone Number', '', '', '', 'Scheduled Days', '', 'Off Days', '', 'Attend Days', '', 'Absent Days']);

        $no = 1;
        foreach ($employees as $emp) {
            $totalWork = 0;
            $totalOff = 0;

            $schedulesMap = $emp->schedules->keyBy(function ($item) {
                return $item->date instanceof Carbon ? $item->date->format('Y-m-d') : (string)$item->date;
            });

            for ($d = 1; $d <= $totalDays; $d++) {
                $dateStr = Carbon::createFromDate($this->year, $this->month, $d)->format('Y-m-d');
                $sched = $schedulesMap->get($dateStr);

                if ($sched && $sched->shift) {
                    if ($sched->shift->is_off) {
                        $totalOff++;
                    } else {
                        $totalWork++;
                    }
                }
            }

            $rows->push([
                $no++,
                $emp->name,
                $emp->phone_number ?? '-',
                '',
                '',
                '',
                $totalWork,
                '',
                $totalOff,
                '',
                '',
                '',
                ''
            ]);
        }

        return $rows;
    }

    private function getShiftCode($schedule)
    {
        if (!$schedule || !$schedule->shift) {
            return '-';
        }

        if ($schedule->shift->is_off) {
            return 'L';
        }

        return $this->extractShiftCode($schedule->shift->shift_name);
    }

    private function extractShiftCode($shiftName)
    {
        $nameLower = strtolower($shiftName);

        // Jika nama shift mengandung Office Hour / OH
        if (str_contains($nameLower, 'office') || str_contains($nameLower, 'oh')) {
            return 'OH';
        }

        if (preg_match('/\d+/', $shiftName, $matches)) {
            return $matches[0];
        }

        $code = '';
        foreach (explode(' ', $shiftName) as $word) {
            $code .= strtoupper(substr($word, 0, 1));
        }

        return $code ?: '1';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Calibri')->setSize(11);

        $sheet->getColumnDimension('A')->setWidth(4.71);
        $sheet->getColumnDimension('B')->setWidth(20.00);
        $sheet->getColumnDimension('C')->setWidth(26.00);
        $sheet->getColumnDimension('D')->setWidth(14.00);
        $sheet->getColumnDimension('U')->setWidth(8.14);

        $dateColumns = ['D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S'];

        $thinBorder = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];

        $fillYellow = ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'FFFF00']]; // Shift 1
        $fillOrange = ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'FFC000']]; // Shift 2
        $fillBlue   = ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '9BC2E6']]; // Shift 3
        $fillGreen  = ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'E2EFDA']]; // OH (Office Hour)
        $fillRed    = ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'FF0000']]; // Sunday

        $sheet->getStyle('A1:U200')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A1:A200')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D1:S200')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('C1:U1');
        $sheet->mergeCells('C2:U2');
        $sheet->getStyle('C1:U2')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('C1:U2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(21);
        $sheet->getRowDimension(2)->setRowHeight(21);

        $sheet->getStyle('P5:S5')->getFont()->setBold(true);

        $highestRow = $sheet->getHighestRow();
        $summaryHeaderRow = null;
        $isCommentSection = false;
        $commentStartRow = null;

        for ($row = 1; $row <= $highestRow; $row++) {
            $valA = trim((string)$sheet->getCell('A' . $row)->getValue());
            $valB = trim((string)$sheet->getCell('B' . $row)->getValue());
            $valC = trim((string)$sheet->getCell('C' . $row)->getValue());

            if ($valB === 'NAME' || $valC === 'MONTH' || $valC === 'DAY' || $valC === 'DATE') {
                $sheet->getStyle("B{$row}:S{$row}")->getFont()->setBold(true);
                $sheet->getStyle("B{$row}:S{$row}")->applyFromArray($thinBorder);
                $sheet->getStyle("B{$row}:S{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }

            if ($valC === 'DAY') {
                foreach ($dateColumns as $col) {
                    $dayVal = trim((string)$sheet->getCell($col . $row)->getValue());
                    if ($dayVal === 'Sun' || $dayVal === 'Minggu' || $dayVal === 'Min') {
                        $sheet->getStyle($col . $row)->getFill()->applyFromArray($fillRed);
                        $sheet->getStyle($col . $row)->getFont()->getColor()->setRGB('FFFFFF');
                        $sheet->getStyle($col . $row)->getFont()->setBold(true);
                    }
                }
            }

            if ($valB === 'NAME') {
                $sheet->mergeCells("B{$row}:B" . ($row + 2));
                $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("B{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            }

            if (is_numeric($valA) && (int)$valA > 0 && ($summaryHeaderRow === null || $row < $summaryHeaderRow)) {
                $sheet->getStyle("B{$row}:S{$row}")->applyFromArray($thinBorder);

                foreach ($dateColumns as $col) {
                    $shiftVal = trim((string)$sheet->getCell($col . $row)->getValue());

                    if ($shiftVal === '1') {
                        $sheet->getStyle($col . $row)->getFill()->applyFromArray($fillYellow);
                    } elseif ($shiftVal === '2') {
                        $sheet->getStyle($col . $row)->getFill()->applyFromArray($fillOrange);
                    } elseif ($shiftVal === '3') {
                        $sheet->getStyle($col . $row)->getFill()->applyFromArray($fillBlue);
                    } elseif ($shiftVal === 'OH') {
                        $sheet->getStyle($col . $row)->getFill()->applyFromArray($fillGreen);
                    }
                }
            }

            if ($valB === 'Comment:') {
                $isCommentSection = true;
                $commentStartRow = $row;
            }

            if ($isCommentSection && $valB !== 'Note:' && $valB !== 'Comment:') {
                $sheet->getStyle("C{$row}:D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                if ($valC === '1') {
                    $sheet->getStyle("C{$row}")->getFill()->applyFromArray($fillYellow);
                } elseif ($valC === '2') {
                    $sheet->getStyle("C{$row}")->getFill()->applyFromArray($fillOrange);
                } elseif ($valC === '3') {
                    $sheet->getStyle("C{$row}")->getFill()->applyFromArray($fillBlue);
                } elseif ($valC === 'OH') {
                    $sheet->getStyle("C{$row}")->getFill()->applyFromArray($fillGreen);
                }
            }

            if ($valB === 'Note:' || str_contains($valC, 'If you want to take a leave')) {
                if ($commentStartRow !== null) {
                    $sheet->getStyle("C" . ($commentStartRow + 1) . ":D" . ($row - 2))->applyFromArray($thinBorder);
                }
                $isCommentSection = false;
                $sheet->getStyle("B{$row}:U{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            }

            if ($valB === 'Name' && $valC === 'Phone Number') {
                $summaryHeaderRow = $row;

                $sheet->mergeCells("C{$row}:F{$row}");
                $sheet->mergeCells("G{$row}:H{$row}");
                $sheet->mergeCells("I{$row}:J{$row}");
                $sheet->mergeCells("K{$row}:L{$row}");
                $sheet->mergeCells("M{$row}:N{$row}");

                $sheet->getStyle("B{$row}:N{$row}")->getFont()->setBold(true);
                $sheet->getStyle("B{$row}:N{$row}")->applyFromArray($thinBorder);
                $sheet->getStyle("B{$row}:N{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }

            if ($summaryHeaderRow !== null && $row > $summaryHeaderRow && is_numeric($valA)) {
                $sheet->mergeCells("C{$row}:F{$row}");
                $sheet->mergeCells("G{$row}:H{$row}");
                $sheet->mergeCells("I{$row}:J{$row}");
                $sheet->mergeCells("K{$row}:L{$row}");
                $sheet->mergeCells("M{$row}:N{$row}");

                $sheet->getStyle("B{$row}:N{$row}")->applyFromArray($thinBorder);
                $sheet->getStyle("G{$row}:N{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
        }

        return [];
    }
}
