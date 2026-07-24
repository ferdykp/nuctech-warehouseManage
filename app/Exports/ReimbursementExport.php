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

    /**
     * Ambil data reimbursement berdasarkan hak akses dan filter
     */
    /**
     * Ambil data reimbursement berdasarkan hak akses dan filter
     */
    public function collection()
    {
        $user = Auth::user();
        $query = Reimbursement::query();

        // 1. FILTER BERDASARKAN HAK AKSES / SITE
        // Jika BUKAN Superadmin dan TIDAK MINTA All Site:
        if (!$this->isAllSite && $user->role !== 'superadmin') {
            if ($user->site_id) {
                // Filter berdasarkan site_id milik User yang membuat reimbursement
                $query->whereHas('user', function ($q) use ($user) {
                    $q->where('site_id', $user->site_id);
                });
            } else {
                // Fallback jika user tidak punya site_id, filter berdasarkan user_id pengunduh
                $query->where('user_id', $user->id);
            }
        }

        // 2. FILTER BULAN (jika ada)
        if ($this->month) {
            $query->whereMonth('date', $this->month);
        }

        // 3. FILTER LIVE SEARCH (jika ada)
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('person_name', 'like', "%{$this->search}%")
                    ->orWhere('comment', 'like', "%{$this->search}%");
            });
        }

        return $query->latest('date')->get();
    }
    /**
     * Mapping kosong untuk mencegah dump data model otomatis ke arah kanan (J ke kanan)
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
                $mergeRanges = [];

                // 3. Loop per Kategori untuk membangun baris Excel
                foreach ($categories as $catKey => $items) {
                    $startCatRow = $currentRow;

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

                    $endCatRow = $currentRow - 1;

                    if ($startCatRow <= $endCatRow) {
                        $mergeRanges[] = "B{$startCatRow}:B{$endCatRow}";
                    }
                }

                // 4. BAGIAN TOTAL & FOOTER
                $totalRowStart = $currentRow;

                $sheet->mergeCells("D{$totalRowStart}:F{$totalRowStart}");
                $sheet->setCellValue("D{$totalRowStart}", "Total Amount (IDR)");
                $sheet->setCellValue("G{$totalRowStart}", "=SUM(G3:G" . ($totalRowStart - 1) . ")");

                $exchangeRow = $totalRowStart + 1;
                $sheet->mergeCells("D{$exchangeRow}:F{$exchangeRow}");
                $sheet->setCellValue("D{$exchangeRow}", "Exchange Rate");

                $cnyRow = $totalRowStart + 2;
                $sheet->mergeCells("D{$cnyRow}:F{$cnyRow}");
                $sheet->setCellValue("D{$cnyRow}", "Total Amount (CNY)");

                $lastRow = $cnyRow;

                // 5. STYLING FORMATTING
                $sheet->mergeCells('A1:H1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                foreach ($mergeRanges as $range) {
                    $sheet->mergeCells($range);
                }

                $sheet->getStyle('A2:H2')->getFont()->setBold(true)->setSize(10);
                $sheet->getStyle('A2:H2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle('A2:H2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('F8FAFC');

                $sheet->getStyle("A3:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("B3:B" . ($totalRowStart - 1))->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("C3:C" . ($totalRowStart - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getStyle("D{$totalRowStart}:F{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle("D{$totalRowStart}:H{$lastRow}")->getFont()->setBold(true);

                $sheet->getStyle("G3:G{$lastRow}")->getFont()->setBold(true);
                $sheet->getStyle("G3:G{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("G3:G{$lastRow}")->getNumberFormat()->setFormatCode('"IDR " #,##0');

                $borderStyle = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => '262626'],
                        ],
                    ],
                ];
                $sheet->getStyle("A2:H" . ($totalRowStart - 1))->applyFromArray($borderStyle);
                $sheet->getStyle("D{$totalRowStart}:G{$lastRow}")->applyFromArray($borderStyle);

                foreach (range('A', 'H') as $columnID) {
                    $sheet->getColumnDimension($columnID)->setAutoSize(true);
                }
            }
        ];
    }
}
