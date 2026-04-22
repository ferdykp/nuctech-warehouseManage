<?php

namespace App\Imports;

use App\Models\Sparepart;
use App\Models\SparepartStock;
use App\Models\SparepartHistory;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\DB;

class SparepartImport implements ToModel, WithHeadingRow
{
    protected $siteId;

    public function __construct($siteId)
    {
        $this->siteId = $siteId;
    }

    public function model(array $row)
    {
        return DB::transaction(function () use ($row) {
            // 1. Update atau Create data sparepart berdasarkan Serial Number 
            $sparepart = Sparepart::updateOrCreate(
                ['serial_number' => $row['serial_number']],
                [
                    'item_name' => $row['item_name'],
                    'type'      => $row['type'],
                    'uom'       => $row['uom'],
                    'note'      => $row['note'] ?? null,
                ]
            );

            // 2. Simpan Stok ke Site terkait
            SparepartStock::updateOrCreate(
                [
                    'sparepart_id' => $sparepart->id,
                    'site_id'      => $this->siteId,
                    'condition'    => strtolower($row['condition']),
                ],
                ['qty' => $row['qty']]
            );

            // 3. Catat History sebagai 'IMPORT'
            SparepartHistory::create([
                'sparepart_id' => $sparepart->id,
                'to_site_id'   => $this->siteId,
                'action'       => 'CREATE',
                'condition'    => strtolower($row['condition']),
                'qty'          => $row['qty'],
                'note'         => 'Import via Excel'
            ]);

            return $sparepart;
        });
    }
}