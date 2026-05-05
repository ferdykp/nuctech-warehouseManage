<?php

namespace App\Exports;

use App\Models\Sparepart;
use App\Models\Site;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class SparepartExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithDrawings,
    WithEvents
{
    protected $data;
    protected $siteId;
    protected $machineName; // Menampung machine_name untuk judul

    public function __construct(string $siteCode)
    {
        $site = Site::where('slug', $siteCode)->firstOrFail();
        $this->siteId = $site->id;
        $this->machineName = $site->machine_name; // Mengambil machine_name sesuai kode blade Anda

        $this->data = Sparepart::whereHas('stocks', function ($q) {
            $q->where('site_id', $this->siteId);
        })->with(['stocks' => function ($q) {
            $q->where('site_id', $this->siteId);
        }])->get();
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            // Baris 1: Judul Site (Akan di-merge di AfterSheet)
            [$this->machineName],
            // Baris 2: Header Kolom
            [
                'No',
                'Item Name',
                'Serial Number',
                'Type',
                'Stock Quantity',
                'Condition',
                'Note',
                'Image',
            ]
        ];
    }

    public function map($row): array
    {
        static $no = 1;
        $totalQty = $row->stocks->sum('qty');
        $conditions = $row->stocks->pluck('condition')->unique()->implode(', ');
        $stockAndUom = $totalQty . ' ' . $row->uom;

        return [
            $no++,
            $row->item_name,
            $row->serial_number,
            $row->type,
            $stockAndUom,
            strtoupper($conditions),
            $row->note,
            '',
        ];
    }

    public function drawings()
    {
        $drawings = [];
        foreach ($this->data as $index => $item) {
            if ($item->image && file_exists(storage_path('app/public/' . $item->image))) {
                $drawing = new Drawing();
                $drawing->setName($item->item_name);
                $drawing->setPath(storage_path('app/public/' . $item->image));
                $drawing->setHeight(60);

                // Koordinat dimulai dari Baris 3 (karena baris 1 judul, baris 2 header)
                $drawing->setCoordinates('H' . ($index + 3));
                $drawing->setOffsetX(10);
                $drawing->setOffsetY(10);
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

                // 1. Merge Baris Judul (A1 sampai H1)
                $sheet->mergeCells('A1:H1');

                // 2. Styling Judul (Machine Name)
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 14,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '4F46E5'], // Warna Indigo
                    ],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(30);

                // 3. Styling Header Kolom (Baris 2)
                $sheet->getStyle('A2:H2')->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // 4. Pengaturan Lebar Kolom
                $sheet->getColumnDimension('A')->setWidth(5);
                $sheet->getColumnDimension('B')->setWidth(25);
                $sheet->getColumnDimension('C')->setWidth(20);
                $sheet->getColumnDimension('D')->setWidth(15);
                $sheet->getColumnDimension('E')->setWidth(15);
                $sheet->getColumnDimension('F')->setWidth(15);
                $sheet->getColumnDimension('G')->setWidth(30);
                $sheet->getColumnDimension('H')->setWidth(20);

                // 5. Styling Baris Data (Mulai Baris 3)
                foreach ($this->data as $index => $item) {
                    $currentRow = $index + 3;
                    $sheet->getRowDimension($currentRow)->setRowHeight(70);
                    $sheet->getStyle('A' . $currentRow . ':G' . $currentRow)
                        ->getAlignment()
                        ->setVertical(Alignment::VERTICAL_CENTER);
                }
            },
        ];
    }
}
