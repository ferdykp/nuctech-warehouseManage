<?php

namespace App\Exports;

use App\Models\Site;
use App\Models\SparepartStock;
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
    protected $stocks;
    protected $site;

    public function __construct(string $siteCode)
    {
        $this->site = Site::with('branch')->where('slug', $siteCode)->firstOrFail();

        // Ambil stok per baris spesifik (kondisi terpisah)
        $this->stocks = SparepartStock::with(['sparepart.category', 'site'])
            ->where('site_id', $this->site->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function collection()
    {
        return $this->stocks;
    }

    /**
     * Data dimulai dari baris ke-6 agar baris 1-4 bisa dipakai untuk Judul Site/Branch
     */
    public function startCell(): string
    {
        return 'A5';
    }

    public function headings(): array
    {
        return [
            'NO',
            'CATEGORY',
            'ITEM NAME',
            'SERIAL NUMBER',
            'TYPE / MODEL',
            'QTY',
            'UOM',
            'CONDITION',
            'REMARKS / NOTE',
            'ATTACHMENT',
        ];
    }

    public function map($stock): array
    {
        static $no = 1;
        $sparepart = $stock->sparepart;

        $conditionLabel = match (strtolower($stock->condition)) {
            'new'        => 'NEW',
            'used-good'  => 'USED (GOOD)',
            'damaged'    => 'DAMAGED',
            'repair'     => 'REPAIRED',
            default      => strtoupper($stock->condition),
        };

        return [
            $no++,
            $sparepart?->category?->name ?? 'Uncategorized',
            $sparepart?->item_name ?? '-',
            $sparepart?->serial_number ?? '-',
            $sparepart?->type ?? '-',
            $stock->qty,
            strtoupper($sparepart?->uom ?? 'PCS'),
            $conditionLabel,
            $sparepart?->note ?? '-',
            '', // Kolom J untuk gambar
        ];
    }

    public function drawings()
    {
        $drawings = [];
        $startRow = 6; // Baris data pertama adalah baris 6 (karena header di baris 5)

        foreach ($this->stocks as $index => $stock) {
            $sparepart = $stock->sparepart;
            if ($sparepart && $sparepart->image && file_exists(storage_path('app/public/' . $sparepart->image))) {
                $drawing = new Drawing();
                $drawing->setName($sparepart->item_name);
                $drawing->setPath(storage_path('app/public/' . $sparepart->image));
                $drawing->setHeight(55);

                $currentRow = $startRow + $index;
                $drawing->setCoordinates('J' . $currentRow);
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
                $exportDate = now()->translatedFormat('d F Y, H:i') . ' WIB';

                // ==========================================
                // 1. HEADER TITLE BANNER (BARIS 1 - 3)
                // ==========================================
                $sheet->mergeCells('A1:J1');
                $sheet->setCellValue('A1', 'SITE INVENTORY MONITORING REPORT');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '0F172A']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);

                $sheet->mergeCells('A2:J2');
                $sheet->setCellValue('A2', "LOCATION: {$machineName}  •  BRANCH: {$branchName}");
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '475569']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);

                $sheet->mergeCells('A3:J3');
                $sheet->setCellValue('A3', "Exported Date: {$exportDate}");
                $sheet->getStyle('A3')->applyFromArray([
                    'font' => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '64748B']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);

                $sheet->getRowDimension(1)->setRowHeight(24);
                $sheet->getRowDimension(2)->setRowHeight(18);
                $sheet->getRowDimension(3)->setRowHeight(16);
                $sheet->getRowDimension(4)->setRowHeight(10); // Spasi kosong

                // ==========================================
                // 2. HEADER TABEL (BARIS 5)
                // ==========================================
                $sheet->getStyle('A5:J5')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1E293B'], // Slate 800 (Corporate Dark)
                    ],
                ]);
                $sheet->getRowDimension(5)->setRowHeight(28);

                // ==========================================
                // 3. LEBAR KOLOM (AUTOFIT & STYLED)
                // ==========================================
                $sheet->getColumnDimension('A')->setWidth(7);   // NO
                $sheet->getColumnDimension('B')->setWidth(18);  // CATEGORY
                $sheet->getColumnDimension('C')->setWidth(30);  // ITEM NAME
                $sheet->getColumnDimension('D')->setWidth(20);  // SERIAL NUMBER
                $sheet->getColumnDimension('E')->setWidth(18);  // TYPE
                $sheet->getColumnDimension('F')->setWidth(10);  // QTY
                $sheet->getColumnDimension('G')->setWidth(10);  // UOM
                $sheet->getColumnDimension('H')->setWidth(16);  // CONDITION
                $sheet->getColumnDimension('I')->setWidth(28);  // REMARKS
                $sheet->getColumnDimension('J')->setWidth(18);  // ATTACHMENT

                // ==========================================
                // 4. STYLING BARIS DATA & ZEBRA STRIPING
                // ==========================================
                $totalData = count($this->stocks);
                $startRow = 6;
                $endRow = $startRow + $totalData - 1;

                if ($totalData > 0) {
                    for ($i = 0; $i < $totalData; $i++) {
                        $currentRow = $startRow + $i;
                        $stock = $this->stocks[$i];

                        $sheet->getRowDimension($currentRow)->setRowHeight(55);

                        // Alignment
                        $sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle('B' . $currentRow . ':E' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                        $sheet->getStyle('F' . $currentRow . ':H' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle('I' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                        $sheet->getStyle('A' . $currentRow . ':J' . $currentRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

                        // Zebra striping (Selang seling warna abu muda)
                        if ($i % 2 === 1) {
                            $sheet->getStyle('A' . $currentRow . ':J' . $currentRow)->applyFromArray([
                                'fill' => [
                                    'fillType' => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => 'F8FAFC'],
                                ],
                            ]);
                        }

                        // Style Warna Teks Kondisi
                        $conditionColor = match (strtolower($stock->condition)) {
                            'new'       => '059669', // Emerald Green
                            'used-good' => '2563EB', // Blue
                            'damaged'   => 'DC2626', // Red
                            'repair'    => 'D97706', // Amber
                            default     => '475569',
                        };

                        $sheet->getStyle('H' . $currentRow)->applyFromArray([
                            'font' => ['bold' => true, 'color' => ['rgb' => $conditionColor]],
                        ]);

                        // Bold untuk QTY
                        $sheet->getStyle('F' . $currentRow)->applyFromArray([
                            'font' => ['bold' => true],
                        ]);
                    }

                    // Border untuk seluruh tabel
                    $sheet->getStyle('A5:J' . $endRow)->applyFromArray([
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

                    $sheet->getStyle('A' . $summaryRow . ':J' . $summaryRow)->applyFromArray([
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
