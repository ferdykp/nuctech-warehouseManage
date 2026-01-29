<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SparepartStocksSeeder extends Seeder
{
    public function run(): void
    {
        // Pastikan sparepart_id sesuai dengan data SparepartsSeeder (1..3)
        \App\Models\SparepartStock::create([
            'sparepart_id' => 1,
            'site_id'      => 1,
            'stock'        => 50,
            'condition'    => 'good',
        ]);

        \App\Models\SparepartStock::create([
            'sparepart_id' => 2,
            'site_id'      => 2,
            'stock'        => 30,
            'condition'    => 'good',
        ]);

        \App\Models\SparepartStock::create([
            'sparepart_id' => 3,
            'site_id'      => 3,
            'stock'        => 20,
            'condition'    => 'good',
        ]);
    }
}
