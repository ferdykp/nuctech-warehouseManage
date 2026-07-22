<?php
// database/seeders/SiteSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Site;
use App\Models\Branch;

class SiteSeeder extends Seeder
{
    public function run(): void
    {
        // Get branches by code
        $jkt = Branch::where('branch_code', 'JKT')->first();
        $sby = Branch::where('branch_code', 'SBY')->first();
        $smg = Branch::where('branch_code', 'SMG')->first();

        $sites = [
            [
                'branch_id'    => $jkt?->id,
                'machine_name' => 'E-Beam',
                'slug'         => 'ebeam-1',
                'location'     => '--'
            ],
            [
                'branch_id'    => $jkt?->id,
                'machine_name' => 'FS6000 Jakarta',
                'slug'         => 'fs6000jkt-1',
                'location'     => '--'
            ],
            [
                'branch_id'    => $sby?->id,
                'machine_name' => 'FS6000 Surabaya',
                'slug'         => 'fs6000sby-1',
                'location'     => '--'
            ],
            [
                'branch_id'    => $smg?->id,
                'machine_name' => 'FS6000 Semarang',
                'slug'         => 'fs6000smg-1',
                'location'     => '--'
            ],
            [
                'branch_id'    => $smg?->id,
                'machine_name' => 'CTMIC',
                'slug'         => 'ctmic-1',
                'location'     => '--'
            ],
        ];

        foreach ($sites as $site) {
            if ($site['branch_id']) {
                Site::create($site);
            }
        }
    }
}
