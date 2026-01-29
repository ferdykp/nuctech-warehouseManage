<?php

namespace App\Exports;

use App\Models\ebeam;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class EbeamExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithDrawings,
    WithEvents
{
    protected $data;

    public function __construct()
    {
        $this->data = ebeam::all();
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
            'Type',
            'Stock',
            'Date Update',
            'Location',
            'Note',
            'Image',
        ];
    }

    public function map($row): array
    {
        static $no = 1;

        return [
            $no++,
            $row->item_name,
            $row->type,
            $row->stock,
            $row->date_update,
            $row->location,
            $row->note,
            '', // kolom gambar dikosongkan, gambar diisi via Drawing
        ];
    }

    /**
     * Tampilkan gambar di Excel
     */
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
                $drawing->setCoordinates('H' . ($index + 2)); // kolom Image

                $drawings[] = $drawing;
            }
        }

        return $drawings;
    }

    /**
     * Styling Excel
     */
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
                $sheet->getColumnDimension('E')->setWidth(18);
                $sheet->getColumnDimension('F')->setWidth(20);
                $sheet->getColumnDimension('G')->setWidth(30);
                $sheet->getColumnDimension('H')->setWidth(20);

                // Tinggi baris agar gambar tidak terpotong
                foreach ($this->data as $index => $item) {
                    if ($item->image) {
                        $sheet->getRowDimension($index + 2)->setRowHeight(70);
                    }
                }
            },
        ];
    }
}
