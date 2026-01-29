<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SparepartHistoriesSeeder extends Seeder
{
    public function run(): void
    {
        // Contoh history untuk sparepart 1 berpindah dari site 1 ke site 2
        \App\Models\SparepartHistory::create([
            'sparepart_id'  => 1,
            'from_site_id'  => 1,
            'to_site_id'    => 2,
            'action'        => 'moved',
            'old_condition' => 'good',
            'new_condition' => 'good',
            'quantity'      => 10,
            'note'          => 'Transfer antar site',
        ]);

        // Contoh history untuk sparepart 2 dibuat
        \App\Models\SparepartHistory::create([
            'sparepart_id'  => 2,
            'from_site_id'  => null,
            'to_site_id'    => 2,
            'action'        => 'created',
            'old_condition' => null,
            'new_condition' => 'good',
            'quantity'      => 30,
            'note'          => 'Initial stock',
        ]);
    }
}
