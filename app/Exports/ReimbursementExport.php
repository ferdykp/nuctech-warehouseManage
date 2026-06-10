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

class ReimbursementExport implements FromCollection, WithHeadings, WithMapping, WithEvents, WithTitle
{
    /**
     * Ambil data yang berstatus approved
     */
    public function collection()
    {
        return Reimbursement::where('status', 'approved')
            ->latest()
            ->get();
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
     * Struktur Header Utama yang Disesuaikan
     * Kolom: A=SN, B=Expense Category, C=Date, D=From, E=To, F=Person Name, G=Amount, H=Comment
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

                // Ambil data asli dari database
                $data = $this->collection();

                // Pisahkan data berdasarkan kategori
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

                // Loop per Kategori untuk membangun baris demi baris secara kustom di kolom A-H
                foreach ($categories as $catKey => $items) {
                    $startCatRow = $currentRow;
                    $snCounter = 1;

                    if ($items->count() > 0) {
                        foreach ($items as $item) {
                            $sheet->setCellValue('A' . $currentRow, $snCounter++);
                            $sheet->setCellValue('B' . $currentRow, $categoryLabels[$catKey]);

                            // Format Tanggal bersih Y-m-d
                            $cleanDate = $item->date ? date('Y-m-d', strtotime($item->date)) : '-';
                            $sheet->setCellValue('C' . $currentRow, $cleanDate);

                            $sheet->setCellValue('D' . $currentRow, $item->from_location ?? '-');
                            $sheet->setCellValue('E' . $currentRow, $item->to_location ?? '-');
                            $sheet->setCellValue('F' . $currentRow, $item->person_name);

                            // 🟩 MODIFIKASI: Langsung tulis nilai nominal ke kolom G (Tanpa kolom teks IDR terpisah)
                            $sheet->setCellValue('G' . $currentRow, $item->amount);

                            // Geser Comment ke kolom H
                            $sheet->setCellValue('H' . $currentRow, $item->comment ?? '-');

                            $currentRow++;
                        }
                    } else {
                        // Jika data kategori tersebut kosong
                        $sheet->setCellValue('A' . $currentRow, '');
                        $sheet->setCellValue('B' . $currentRow, $categoryLabels[$catKey]);
                        $sheet->setCellValue('C' . $currentRow, '');
                        $sheet->setCellValue('D' . $currentRow, '');
                        $sheet->setCellValue('E' . $currentRow, '');
                        $sheet->setCellValue('F' . $currentRow, '');
                        $sheet->setCellValue('G' . $currentRow, ''); // Nominal kosong
                        $sheet->setCellValue('H' . $currentRow, ''); // Comment kosong

                        $currentRow++;
                    }

                    $endCatRow = $currentRow - 1;

                    if ($startCatRow <= $endCatRow) {
                        $mergeRanges[] = "B{$startCatRow}:B{$endCatRow}";
                    }
                }

                // Tambahkan Baris Total di bagian bawah tabel
                $totalRowStart = $currentRow;

                // Gabungkan kolom D sampai F untuk tulisan label total
                $sheet->mergeCells("D{$totalRowStart}:F{$totalRowStart}");
                $sheet->setCellValue("D{$totalRowStart}", "Total Amount (IDR)");

                // Rumus SUM dinamis mengarah ke seluruh data di kolom G (G3 sampai baris data terakhir)
                $sheet->setCellValue("G{$totalRowStart}", "=SUM(G3:G" . ($totalRowStart - 1) . ")");

                // Baris Exchange Rate
                $exchangeRow = $totalRowStart + 1;
                $sheet->mergeCells("D{$exchangeRow}:F{$exchangeRow}");
                $sheet->setCellValue("D{$exchangeRow}", "Exchange Rate");

                // Baris Total Amount CNY
                $cnyRow = $totalRowStart + 2;
                $sheet->mergeCells("D{$cnyRow}:F{$cnyRow}");
                $sheet->setCellValue("D{$cnyRow}", "Total Amount (CNY)");

                $lastRow = $cnyRow;

                // PROSES STYLING & FORMATTING 
                // Judul atas dimerge dari A1 sampai H1
                $sheet->mergeCells('A1:H1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                foreach ($mergeRanges as $range) {
                    $sheet->mergeCells($range);
                }

                // Styling Header Tabel (Baris 2)
                $sheet->getStyle('A2:H2')->getFont()->setBold(true)->setSize(10);
                $sheet->getStyle('A2:H2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle('A2:H2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('F8FAFC');

                // Alignment Data Konten
                $sheet->getStyle("A3:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("B3:B" . ($totalRowStart - 1))->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("C3:C" . ($totalRowStart - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Alignment teks area total di bawah (D sampai F digeser ke kanan)
                $sheet->getStyle("D{$totalRowStart}:F{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle("D{$totalRowStart}:H{$lastRow}")->getFont()->setBold(true);

                // 🟩 FORMATTING NOMINAL: Gabungkan format prefix teks "IDR " langsung ke dalam style angka di kolom G
                $sheet->getStyle("G3:G{$lastRow}")->getFont()->setBold(true);
                $sheet->getStyle("G3:G{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("G3:G{$lastRow}")->getNumberFormat()->setFormatCode('"IDR " #,##0');

                // Penerapan border kotak hitam tipis
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

                // Set Auto Width kolom A-H
                foreach (range('A', 'H') as $columnID) {
                    $sheet->getColumnDimension($columnID)->setAutoSize(true);
                }
            }
        ];
    }
}
