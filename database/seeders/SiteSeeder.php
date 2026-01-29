<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Site; // Pastikan nama model sesuai dengan projectmu (Site)
use Illuminate\Support\Carbon;

class SiteSeeder extends Seeder
{
    public function run(): void
    {
        $sites = [
            [
                'code'         => 'ebeam',
                'name'         => 'E-Beam',
                'machine_type' => 'ebeam',
                'is_active'    => true,
            ],
            [
                'code'         => 'fsjkt',
                'name'         => 'FS6000 Jakarta',
                'machine_type' => 'fs6000',
                'is_active'    => true,
            ],
            [
                'code'         => 'fssby',
                'name'         => 'FS6000 Surabaya',
                'machine_type' => 'fs6000',
                'is_active'    => true,
            ],
            [
                'code'         => 'fssmg',
                'name'         => 'FS6000 Semarang',
                'machine_type' => 'fs6000',
                'is_active'    => true,
            ],
            [
                'code'         => 'ctmic',
                'name'         => 'CTMIC',
                'machine_type' => 'ctmic',
                'is_active'    => true,
            ],
        ];

        foreach ($sites as $site) {
            Site::create($site);
        }
    }
}
