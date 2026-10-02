<?php

namespace App\Exports;

use App\Models\Site;
use App\Models\Sparepart;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class SparepartExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithDrawings,
    WithCustomStartCell,
    WithEvents
{
    protected $spareparts;
    protected $site;

    public function __construct(string $siteCode)
    {
        $this->site = Site::with('branch')->where('slug', $siteCode)->firstOrFail();

        // 🟢 Ambil Master Sparepart yang ada di Site ini dan urutkan A-Z
        $this->spareparts = Sparepart::whereHas('stocks', function ($q) {
            $q->where('site_id', $this->site->id);
        })
            ->with(['category', 'stocks' => function ($q) {
                $q->where('site_id', $this->site->id);
            }])
            ->orderBy('item_name', 'asc')
            ->get();
    }

    public function collection()
    {
        return $this->spareparts;
    }

    public function startCell(): string
    {
        return 'A4';
    }

    public function headings(): array
    {
        return [
            'NO',
            'CATEGORY',
            'ITEM NAME',
            'SERIAL NUMBER',
            'TYPE / MODEL',
            'TOTAL QTY',
            'UOM',
            'REMARKS / CONDITION DETAILS',
            'ATTACHMENT',
        ];
    }

    public function map($sparepart): array
    {
        static $no = 1;

        // 1. Hitung TOTAL QTY dari semua kondisi di site ini
        $totalQty = $sparepart->stocks->sum('qty');

        // 2. Susun Rincian Kondisi (misal: "NEW: 15, USED (GOOD): 5")
        $conditionDetails = [];
        foreach ($sparepart->stocks as $stock) {
            if ($stock->qty > 0) {
                $condName = match (strtolower($stock->condition)) {
                    'new'        => 'NEW',
                    'used-good'  => 'USED (GOOD)',
                    'damaged'    => 'DAMAGED',
                    'repair'     => 'REPAIRED',
                    default      => strtoupper($stock->condition),
                };
                $conditionDetails[] = "{$condName}: {$stock->qty}";
            }
        }

        $conditionText = !empty($conditionDetails) ? implode(' | ', $conditionDetails) : '-';

        // Gabungkan catatan manual (jika ada) dengan rincian kondisi
        $finalRemarks = $conditionText;
        if (!empty($sparepart->note)) {
            $finalRemarks .= " (Note: {$sparepart->note})";
        }

        return [
            $no++,
            $sparepart->category?->name ?? 'Uncategorized',
            $sparepart->item_name ?? '-',
            $sparepart->serial_number ?? '-',
            $sparepart->type ?? '-',
            $totalQty,
            strtoupper($sparepart->uom ?? 'PCS'),
            $finalRemarks,
            '', // Kolom I untuk Gambar
        ];
    }

    public function drawings()
    {
        $drawings = [];
        $startRow = 5;

        foreach ($this->spareparts as $index => $sparepart) {
            if ($sparepart->image && file_exists(storage_path('app/public/' . $sparepart->image))) {
                $drawing = new Drawing();
                $drawing->setName($sparepart->item_name);
                $drawing->setPath(storage_path('app/public/' . $sparepart->image));
                $drawing->setHeight(55);

                $currentRow = $startRow + $index;
                $drawing->setCoordinates('I' . $currentRow);
                $drawing->setOffsetX(15);
                $drawing->setOffsetY(8);
                $drawings[] = $drawing;
            }
        }
        return $drawings;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $branchName = $this->site->branch->branch_name ?? 'Unassigned Branch';
                $machineName = $this->site->machine_name ?? 'Site Machine Unit';

                // ==========================================
                // 1. HEADER TITLE BANNER (BARIS 1 - 2, CENTERED)
                // ==========================================
                $sheet->mergeCells('A1:I1');
                $sheet->setCellValue('A1', 'SITE INVENTORY MONITORING REPORT');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '0F172A']],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $sheet->mergeCells('A2:I2');
                $sheet->setCellValue('A2', "LOCATION: {$machineName}   •   BRANCH: {$branchName}");
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '475569']],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $sheet->getRowDimension(1)->setRowHeight(26);
                $sheet->getRowDimension(2)->setRowHeight(20);
                $sheet->getRowDimension(3)->setRowHeight(10);

                // ==========================================
                // 2. HEADER TABEL (BARIS 4)
                // ==========================================
                $sheet->getStyle('A4:I4')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1E293B'], // Slate 800 (Corporate Dark)
                    ],
                ]);
                $sheet->getRowDimension(4)->setRowHeight(28);

                // ==========================================
                // 3. LEBAR KOLOM
                // ==========================================
                $sheet->getColumnDimension('A')->setWidth(7);   // NO
                $sheet->getColumnDimension('B')->setWidth(18);  // CATEGORY
                $sheet->getColumnDimension('C')->setWidth(30);  // ITEM NAME
                $sheet->getColumnDimension('D')->setWidth(20);  // SERIAL NUMBER
                $sheet->getColumnDimension('E')->setWidth(18);  // TYPE
                $sheet->getColumnDimension('F')->setWidth(12);  // TOTAL QTY
                $sheet->getColumnDimension('G')->setWidth(10);  // UOM
                $sheet->getColumnDimension('H')->setWidth(36);  // REMARKS / CONDITION DETAILS
                $sheet->getColumnDimension('I')->setWidth(18);  // ATTACHMENT

                // ==========================================
                // 4. STYLING BARIS DATA & ZEBRA STRIPING
                // ==========================================
                $totalData = count($this->spareparts);
                $startRow = 5;
                $endRow = $startRow + $totalData - 1;

                if ($totalData > 0) {
                    for ($i = 0; $i < $totalData; $i++) {
                        $currentRow = $startRow + $i;

                        $sheet->getRowDimension($currentRow)->setRowHeight(55);

                        // Alignment
                        $sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle('B' . $currentRow . ':E' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                        $sheet->getStyle('F' . $currentRow . ':G' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle('H' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                        $sheet->getStyle('A' . $currentRow . ':I' . $currentRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

                        // Zebra striping
                        if ($i % 2 === 1) {
                            $sheet->getStyle('A' . $currentRow . ':I' . $currentRow)->applyFromArray([
                                'fill' => [
                                    'fillType' => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => 'F8FAFC'],
                                ],
                            ]);
                        }

                        // Bold untuk Total QTY
                        $sheet->getStyle('F' . $currentRow)->applyFromArray([
                            'font' => ['bold' => true, 'color' => ['rgb' => '2563EB']], // Warna Biru Bold
                        ]);
                    }

                    // Border untuk seluruh tabel
                    $sheet->getStyle('A4:I' . $endRow)->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => 'E2E8F0'],
                            ],
                            'outline' => [
                                'borderStyle' => Border::BORDER_MEDIUM,
                                'color' => ['rgb' => '94A3B8'],
                            ],
                        ],
                    ]);

                    // ==========================================
                    // 5. FOOTER SUMMARY / TOTAL
                    // ==========================================
                    $summaryRow = $endRow + 1;
                    $sheet->mergeCells('A' . $summaryRow . ':E' . $summaryRow);
                    $sheet->setCellValue('A' . $summaryRow, 'TOTAL ACCUMULATED STOCK');
                    $sheet->setCellValue('F' . $summaryRow, "=SUM(F{$startRow}:F{$endRow})");

                    $sheet->getStyle('A' . $summaryRow . ':I' . $summaryRow)->applyFromArray([
                        'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '0F172A']],
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'E2E8F0'],
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => 'CBD5E1'],
                            ],
                        ],
                    ]);

                    $sheet->getStyle('A' . $summaryRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle('F' . $summaryRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getRowDimension($summaryRow)->setRowHeight(25);
                }
            },
        ];
    }
}
