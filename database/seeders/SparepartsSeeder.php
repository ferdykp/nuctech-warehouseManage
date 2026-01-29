<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SparepartsSeeder extends Seeder
{
    public function run(): void
    {
        // Sesuaikan dengan id site yang ada setelah SiteSeeder dijalankan
        // Asumsikan situs 1..5 telah terisi (id autoincrement)
        \App\Models\Sparepart::create([
            'owner_site_id'   => 1,
            'current_site_id' => 1,
            'item_name'       => 'Laser Crown',
            'type'            => 'Component',
            'stock'           => 50,
            'uom'             => 'pcs',
            'condition'       => 'good',
            'image'           => null,
            'note'            => 'Original sparepart',
            'save_loc' => null, // <-- tambahkan ini

        ]);

        \App\Models\Sparepart::create([
            'owner_site_id'   => 2,
            'current_site_id' => 2,
            'item_name'       => 'Beam Shield',
            'type'            => 'Consumable',
            'stock'           => 30,
            'uom'             => 'pcs',
            'condition'       => 'good',
            'image'           => null,
            'note'            => 'Second site stock',
            'save_loc' => null, // <-- tambahkan ini

        ]);

        \App\Models\Sparepart::create([
            'owner_site_id'   => 3,
            'current_site_id' => 3,
            'item_name'       => 'Cooling Pad',
            'type'            => 'Accessory',
            'stock'           => 20,
            'uom'             => 'pcs',
            'condition'       => 'good',
            'image'           => null,
            'note'            => 'New arrival',
            'save_loc' => null, // <-- tambahkan ini

        ]);
    }
}
