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

    public function __construct(string $siteCode)
    {
        $site = Site::where('slug', $siteCode)->firstOrFail();

        $this->data = Sparepart::whereHas('stocks', function ($q) use ($site) {
            $q->where('site_id', $site->id);
        })->with('stocks')->get();
    }

    /* =====================
        DATA
    ====================== */
    public function collection()
    {
        return $this->data;
    }

    /* =====================
        HEADER
    ====================== */
    public function headings(): array
    {
        return [
            'No',
            'Serial Number',
            'Item Name',
            'Type',
            'Stock',
            // 'UOM',
            'Condition',
            'Note',
            'Image',
        ];
    }

    /* =====================
        ROW MAPPING
    ====================== */
    public function map($row): array
    {
        static $no = 1;
        $totalQty = $row->stocks->sum('qty');
        $condition = $row->stocks->first() ? $row->stocks->first()->condition : '-';
        $stockAndUom = $totalQty . ' ' . $row->uom;

        return [
            $no++,
            $row->serial_number,
            $row->item_name,
            $row->type,
            // $row->qty,
            // $totalQty,
            // $row->uom,
            $stockAndUom,
            // $row->condition,
            $condition, // Sekarang mengambil dari relasi stocks
            $row->note,
            '', // kolom image (diisi Drawing)
        ];
    }

    /* =====================
        IMAGE
    ====================== */
    public function drawings()
    {
        $drawings = [];

        foreach ($this->data as $index => $item) {
            if ($item->image && file_exists(storage_path('app/public/' . $item->image))) {

                $drawing = new Drawing();
                $drawing->setName($item->item_name);
                $drawing->setDescription('Sparepart Image');
                $drawing->setPath(storage_path('app/public/' . $item->image));
                $drawing->setHeight(60);
                $drawing->setCoordinates('H' . ($index + 2));

                $drawings[] = $drawing;
            }
        }

        return $drawings;
    }

    /* =====================
        STYLING
    ====================== */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();

                // Lebar kolom
                $sheet->getColumnDimension('A')->setWidth(6);
                $sheet->getColumnDimension('B')->setWidth(30);
                $sheet->getColumnDimension('C')->setWidth(20);
                $sheet->getColumnDimension('D')->setWidth(10);
                $sheet->getColumnDimension('E')->setWidth(10);
                $sheet->getColumnDimension('F')->setWidth(15);
                $sheet->getColumnDimension('G')->setWidth(30);
                $sheet->getColumnDimension('H')->setWidth(20);
                // $sheet->getColumnDimension('I')->setWidth(20);


                // Tinggi baris untuk gambar
                foreach ($this->data as $index => $item) {
                    if ($item->image) {
                        $sheet->getRowDimension($index + 2)->setRowHeight(70);
                    }
                }
            },
        ];
    }
}
