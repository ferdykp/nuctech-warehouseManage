<?php

namespace App\Exports;

use App\Models\SparepartStock;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GlobalSparepartExport implements FromQuery, WithMapping, WithHeadings, ShouldAutoSize, WithStyles
{
    protected $search;

    public function __construct($search = null)
    {
        $this->search = $search;
    }

    public function query()
    {
        $query = SparepartStock::with(['sparepart', 'site.branch']);

        if ($this->search) {
            $query->whereHas('sparepart', function ($q) {
                $q->where('item_name', 'like', '%' . $this->search . '%')
                    ->orWhere('serial_number', 'like', '%' . $this->search . '%');
            })->orWhereHas('site', function ($q) {
                $q->where('machine_name', 'like', '%' . $this->search . '%');
            });
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'Item Name',
            'Serial Number',
            'Type/Model',
            'Site Machine',
            'Branch',
            'Quantity',
            'UOM',
            'Condition',
            'Last Updated'
        ];
    }

    public function map($stock): array
    {
        return [
            $stock->sparepart->item_name,
            $stock->sparepart->serial_number,
            $stock->sparepart->type,
            $stock->site->machine_name,
            $stock->site->branch->branch_name ?? '-',
            $stock->qty,
            $stock->sparepart->uom,
            strtoupper(str_replace('-', ' ', $stock->condition)),
            $stock->updated_at->format('d/m/Y H:i'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
