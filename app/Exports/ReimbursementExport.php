<?php

namespace App\Exports;

use App\Models\Reimbursement;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Support\Facades\Auth;

class ReimbursementExport implements FromCollection, WithHeadings, WithMapping, WithEvents, WithTitle
{
    protected $search;
    protected $month;
    protected $isAllSite;

    /**
     * Constructor untuk menerima filter search, month, dan status all_site
     */
    public function __construct($search = null, $month = null, $isAllSite = false)
    {
        $this->search = $search;
        $this->month = $month;
        $this->isAllSite = $isAllSite;
    }

    public function collection()
    {
        $user = Auth::user();
        $query = \App\Services\ReimbursementAccess::query();

        // 1. FILTER BERDASARKAN HAK AKSES / SITE
        if (!$this->isAllSite && $user->role !== 'superadmin') {
            if ($user->site_id) {
                $query->whereHas('user', function ($q) use ($user) {
                    $q->where('site_id', $user->site_id);
                });
            } else {
                $query->where('user_id', $user->id);
            }
        }

        // 2. FILTER BULAN
        if ($this->month) {
            $query->whereMonth('date', $this->month);
        }

        // 3. FILTER LIVE SEARCH
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('person_name', 'like', "%{$this->search}%")
                    ->orWhere('comment', 'like', "%{$this->search}%");
            });
        }

        // Urutkan berdasarkan Kategori -> Nama Karyawan -> Tanggal Invoice -> ID
        return $query
            ->orderByRaw("CASE category WHEN 'transportation' THEN 1 WHEN 'delivery' THEN 2 WHEN 'office' THEN 3 ELSE 4 END")
            ->orderBy('person_name', 'asc')
            ->orderBy('date', 'asc')
            ->orderBy('id', 'asc')
            ->get();
    }

    /**
     * Mapping kosong untuk mencegah dump data model otomatis ke arah kanan
     */
    public function map($reimbursement): array
    {
        return [];
    }

    /**
     * Judul Sheet di bagian bawah
     */
    public function title(): string
    {
        return 'Monthly';
    }

    /**
     * Struktur Header Utama
     */
    public function headings(): array
    {
        return [
            ['EXPENSE RECORD'], // Baris 1: Judul Besar
            ['SN', 'Expense Category', 'Date', 'From', 'To', 'Person Name', 'Amount', 'Comment'] // Baris 2: Table Header
        ];
    }

    /**
     * Mengatur Logic Layout Template via Events AfterSheet
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // 1. Ambil data asli dari database
                $data = $this->collection();

                // Petakan nomor urut (claim_no) ke setiap item ID
                $claimMap = [];
                $counter = 1;
                foreach ($data as $item) {
                    $claimMap[$item->id] = $counter++;
                }

                // 2. Pisahkan data berdasarkan kategori
                $categories = [
                    'transportation' => $data->where('category', 'transportation'),
                    'delivery'       => $data->where('category', 'delivery'),
                    'office'         => $data->where('category', 'office'),
                ];

                $categoryLabels = [
                    'transportation' => 'Transportation',
                    'delivery'       => 'Delivery',
                    'office'         => 'Office'
                ];

                $currentRow = 3;

                // 3. Loop per Kategori untuk membangun baris Excel (Tanpa Merge Kolom B)
                foreach ($categories as $catKey => $items) {
                    if ($items->count() > 0) {
                        foreach ($items as $item) {
                            $snNumber = $claimMap[$item->id] ?? '-';

                            $sheet->setCellValue('A' . $currentRow, $snNumber);
                            $sheet->setCellValue('B' . $currentRow, $categoryLabels[$catKey]);

                            // Format Tanggal bersih Y-m-d
                            $cleanDate = $item->date ? date('Y-m-d', strtotime($item->date)) : '-';
                            $sheet->setCellValue('C' . $currentRow, $cleanDate);

                            $sheet->setCellValue('D' . $currentRow, $item->from_location ?? '-');
                            $sheet->setCellValue('E' . $currentRow, $item->to_location ?? '-');
                            $sheet->setCellValue('F' . $currentRow, $item->person_name);

                            // Nominal ke kolom G
                            $sheet->setCellValue('G' . $currentRow, $item->amount);

                            // Comment ke kolom H
                            $sheet->setCellValue('H' . $currentRow, $item->comment ?? '-');

                            $currentRow++;
                        }
                    } else {
                        // Jika data kategori kosong
                        $sheet->setCellValue('A' . $currentRow, '');
                        $sheet->setCellValue('B' . $currentRow, $categoryLabels[$catKey]);
                        $sheet->setCellValue('C' . $currentRow, '');
                        $sheet->setCellValue('D' . $currentRow, '');
                        $sheet->setCellValue('E' . $currentRow, '');
                        $sheet->setCellValue('F' . $currentRow, '');
                        $sheet->setCellValue('G' . $currentRow, '');
                        $sheet->setCellValue('H' . $currentRow, '');

                        $currentRow++;
                    }
                }

                $endTableDataRow = $currentRow - 1;

                // 4. BAGIAN TOTAL & FOOTER
                $totalRowStart = $currentRow;

                $sheet->mergeCells("D{$totalRowStart}:F{$totalRowStart}");
                $sheet->setCellValue("D{$totalRowStart}", "Total Amount (IDR)");
                $sheet->setCellValue("G{$totalRowStart}", "=SUM(G3:G" . ($totalRowStart - 1) . ")");

                $exchangeRow = $totalRowStart + 1;
                $sheet->mergeCells("D{$exchangeRow}:F{$exchangeRow}");
                $sheet->setCellValue("D{$exchangeRow}", "Exchange Rate");
                $sheet->setCellValue("H{$exchangeRow}", "(filled in by PT UMA)");

                $cnyRow = $totalRowStart + 2;
                $sheet->mergeCells("D{$cnyRow}:F{$cnyRow}");
                $sheet->setCellValue("D{$cnyRow}", "Total Amount (CNY)");
                $sheet->setCellValue("H{$cnyRow}", "(filled in by PT UMA)");

                // 5. BAGIAN TANDA TANGAN (SIGNATURES)
                $sigRow1 = $cnyRow + 4; // Beri jarak 3 baris kosong
                $sigRow2 = $sigRow1 + 1;
                $sigRow3 = $sigRow2 + 3; // Beri ruang kosong untuk tanda tangan fisik/coretan
                $sigRow4 = $sigRow3 + 1;

                // Proposed By (Kolom B-C)
                $sheet->mergeCells("B{$sigRow1}:C{$sigRow1}");
                $sheet->setCellValue("B{$sigRow1}", "Proposed By");
                $sheet->mergeCells("B{$sigRow2}:C{$sigRow2}");
                $sheet->setCellValue("B{$sigRow2}", "Local Team Leader");
                $sheet->mergeCells("B{$sigRow4}:C{$sigRow4}");
                $sheet->setCellValue("B{$sigRow4}", "Rangga Rajasa");

                // Approval By 1 (Kolom E-F)
                $sheet->mergeCells("E{$sigRow1}:F{$sigRow1}");
                $sheet->setCellValue("E{$sigRow1}", "Approval By");
                $sheet->mergeCells("E{$sigRow2}:F{$sigRow2}");
                $sheet->setCellValue("E{$sigRow2}", "Station Master");
                $sheet->mergeCells("E{$sigRow4}:F{$sigRow4}");
                $sheet->setCellValue("E{$sigRow4}", "张举");

                // Approval By 2 (Kolom G-H)
                $sheet->mergeCells("G{$sigRow1}:H{$sigRow1}");
                $sheet->setCellValue("G{$sigRow1}", "Approval By");
                $sheet->mergeCells("G{$sigRow4}:H{$sigRow4}");
                $sheet->setCellValue("G{$sigRow4}", "Mr. Tao Jinbo");

                $lastRow = $sigRow4;

                // 6. STYLING FORMATTING
                $sheet->mergeCells('A1:H1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getStyle('A2:H2')->getFont()->setBold(true)->setSize(10);
                $sheet->getStyle('A2:H2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle('A2:H2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('F8FAFC');

                $sheet->getStyle("A3:A{$endTableDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("B3:B{$endTableDataRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("C3:C{$endTableDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getStyle("D{$totalRowStart}:F{$cnyRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle("D{$totalRowStart}:H{$cnyRow}")->getFont()->setBold(true);

                // Format Nominal Kolom G (Data tabel & Baris Total Amount IDR)
                $sheet->getStyle("G3:G{$endTableDataRow}")->getFont()->setBold(true);
                $sheet->getStyle("G3:G{$endTableDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("G3:G{$endTableDataRow}")->getNumberFormat()->setFormatCode('"IDR " #,##0');

                // Terapkan format angka juga pada baris Total Amount (G{$totalRowStart})
                $sheet->getStyle("G{$totalRowStart}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("G{$totalRowStart}")->getNumberFormat()->setFormatCode('"IDR " #,##0');

                // Styling blok tanda tangan agar rapi dan berada di tengah
                $sigBlockRange = "B{$sigRow1}:H{$sigRow4}";
                $sheet->getStyle($sigBlockRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle("B{$sigRow1}:H{$sigRow2}")->getFont()->setBold(true);
                $sheet->getStyle("B{$sigRow4}:H{$sigRow4}")->getFont()->setBold(true);

                $borderStyle = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => '262626'],
                        ],
                    ],
                ];
                $sheet->getStyle("A2:H{$endTableDataRow}")->applyFromArray($borderStyle);
                $sheet->getStyle("D{$totalRowStart}:G{$cnyRow}")->applyFromArray($borderStyle);

                foreach (range('A', 'H') as $columnID) {
                    $sheet->getColumnDimension($columnID)->setAutoSize(true);
                }
            }
        ];
    }
}
