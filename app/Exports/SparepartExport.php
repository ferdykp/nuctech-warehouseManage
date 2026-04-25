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

class SparepartExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithDrawings,
    WithEvents
{
    protected $data;
    protected $siteId;

    public function __construct(string $siteCode)
    {
        $site = Site::where('slug', $siteCode)->firstOrFail();
        $this->siteId = $site->id;

        // Ambil sparepart yang HANYA ada di site tersebut
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
            'No',
            'Item Name',
            'Serial Number',
            'Type',
            'Stock Quantity',
            'Condition',
            'Note',
            'Image',
        ];
    }

    public function map($row): array
    {
        static $no = 1;

        // Menghitung total qty di site tersebut
        $totalQty = $row->stocks->sum('qty');

        // Menggabungkan semua kondisi yang ada di site tersebut (jika ada lebih dari satu)
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
            '', // Kolom H untuk Image
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
                // Kolom H adalah kolom ke-8
                $drawing->setCoordinates('H' . ($index + 2));
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

                // Styling Header
                $sheet->getStyle('A1:H1')->getFont()->setBold(true);

                // Setting Lebar Kolom
                $sheet->getColumnDimension('A')->setWidth(5);   // No
                $sheet->getColumnDimension('B')->setWidth(25);  // Item Name
                $sheet->getColumnDimension('C')->setWidth(20);  // Serial
                $sheet->getColumnDimension('D')->setWidth(15);  // Type
                $sheet->getColumnDimension('E')->setWidth(15);  // Stock
                $sheet->getColumnDimension('F')->setWidth(15);  // Condition
                $sheet->getColumnDimension('G')->setWidth(30);  // Note
                $sheet->getColumnDimension('H')->setWidth(20);  // Image (Harus lebar untuk gambar)

                // Tinggi baris untuk semua data agar gambar muat
                foreach ($this->data as $index => $item) {
                    $sheet->getRowDimension($index + 2)->setRowHeight(70);
                    // Vertical center agar teks di tengah baris yang tinggi
                    $sheet->getStyle('A' . ($index + 2) . ':G' . ($index + 2))
                        ->getAlignment()
                        ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                }
            },
        ];
    }
}
