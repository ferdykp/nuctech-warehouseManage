<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sparepart;

class SparepartsSeeder extends Seeder
{
    public function run(): void
    {
        $spareparts = [
            [
                'item_name' => 'Laser Crown',
                'serial_number' => 'LC-123456',
                'type' => 'Component',
                'uom' => 'pcs',
                'image' => null,
                'note' => 'Original sparepart',
                'category_id' => 1,
                'source_data' => 'system',
            ],
            [
                'item_name' => 'Beam Shield',
                'serial_number' => 'BS-987654',
                'type' => 'Consumable',
                'uom' => 'pcs',
                'image' => null,
                'note' => 'Replacement shield',
                'category_id' => 4,
                'source_data' => 'system',
            ],
            [
                'item_name' => 'Control Board',
                'serial_number' => 'CB-112233',
                'type' => 'Electrical',
                'uom' => 'pcs',
                'image' => null,
                'note' => 'Main control board',
                'category_id' => 2,
                'source_data' => 'system',
            ],
        ];

        foreach ($spareparts as $sparepart) {
            Sparepart::create($sparepart);
        }
    }
}
