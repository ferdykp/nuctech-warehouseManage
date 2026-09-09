<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Branch;
use App\Models\Site;

class MachineSiteAndBranchSeeder extends Seeder
{
    public function run(): void
    {
        // 1. SEED DATA BRANCHES
        $branches = [
            ['branch_name' => 'SEMARANG',   'branch_code' => 'SMG', 'branch_address' => 'Semarang'],
            ['branch_name' => 'JAKARTA',    'branch_code' => 'JKT', 'branch_address' => 'Jakarta'],
            ['branch_name' => 'SURABAYA',   'branch_code' => 'SUB', 'branch_address' => 'Surabaya'],
            ['branch_name' => 'LAMPUNG',    'branch_code' => 'LPG', 'branch_address' => 'Lampung'],
            ['branch_name' => 'BATAM',      'branch_code' => 'BTM', 'branch_address' => 'Batam'],
            ['branch_name' => 'BALI',       'branch_code' => 'DPS', 'branch_address' => 'Bali'],
            ['branch_name' => 'MEDAN',      'branch_code' => 'KNO', 'branch_address' => 'Medan'],
            ['branch_name' => 'BANYUWANGI', 'branch_code' => 'BWX', 'branch_address' => 'Banyuwangi'],
        ];

        foreach ($branches as $branch) {
            Branch::updateOrCreate(
                ['branch_code' => $branch['branch_code']],
                $branch
            );
        }

        // 2. GET BRANCHES BY CODE
        $smg = Branch::where('branch_code', 'SMG')->first();
        $jkt = Branch::where('branch_code', 'JKT')->first();
        $sub = Branch::where('branch_code', 'SUB')->first();
        $lpg = Branch::where('branch_code', 'LPG')->first();
        $btm = Branch::where('branch_code', 'BTM')->first();
        $dps = Branch::where('branch_code', 'DPS')->first();
        $kno = Branch::where('branch_code', 'KNO')->first();
        $bwx = Branch::where('branch_code', 'BWX')->first();

        // 3. SEED DATA SITES (16 SITES DARI GAMBAR UI)
        $sites = [
            [
                'id'           => 1,
                'branch_id'    => $smg?->id,
                'machine_name' => 'FS6000 Semarang',
                'slug'         => 'fs6000-semarang',
                'location'     => 'PT TPKS Semarang'
            ],
            [
                'id'           => 2,
                'branch_id'    => $jkt?->id,
                'machine_name' => 'FS6000 Jakarta',
                'slug'         => 'fs6000-jakarta',
                'location'     => 'Jakarta Priok'
            ],
            [
                'id'           => 3,
                'branch_id'    => $sub?->id,
                'machine_name' => 'FS6000 Surabaya',
                'slug'         => 'fs6000-surabaya',
                'location'     => 'PT TPS Surabaya'
            ],
            [
                'id'           => 4,
                'branch_id'    => $sub?->id,
                'machine_name' => 'FS6000 Teluk Lamong',
                'slug'         => 'fs6000-teluk-lamong',
                'location'     => 'PT TTL Surabaya'
            ],
            [
                'id'           => 5,
                'branch_id'    => $jkt?->id,
                'machine_name' => 'Office',
                'slug'         => 'office',
                'location'     => 'French Walk Nice Garden MOI'
            ],
            [
                'id'           => 6,
                'branch_id'    => $sub?->id,
                'machine_name' => 'CTMIC2100YW Surabaya',
                'slug'         => 'ctmic2100yw-surabaya',
                'location'     => 'BNN Kab. Banyuwangi'
            ],
            [
                'id'           => 7,
                'branch_id'    => $lpg?->id,
                'machine_name' => 'CTMIC2100YW Lampung',
                'slug'         => 'ctmic2100yw-lampung',
                'location'     => 'BNN Prov. Lampung'
            ],
            [
                'id'           => 8,
                'branch_id'    => $btm?->id,
                'machine_name' => 'CTMIC2100YW Batam',
                'slug'         => 'ctmic2100yw-batam',
                'location'     => 'BNN Prov. Kepulauan Riau'
            ],
            [
                'id'           => 9,
                'branch_id'    => $dps?->id,
                'machine_name' => 'E-dog Bali',
                'slug'         => 'e-dog-bali',
                'location'     => 'BNN Prov. Bali'
            ],
            [
                'id'           => 10,
                'branch_id'    => $jkt?->id,
                'machine_name' => 'E-dog Jakarta',
                'slug'         => 'e-dog-jakarta',
                'location'     => 'BNN RI Jakarta'
            ],
            [
                'id'           => 11,
                'branch_id'    => $kno?->id,
                'machine_name' => 'E-dog Medan',
                'slug'         => 'e-dog-medan',
                'location'     => 'BNN Prov. Sumatra Utara'
            ],
            [
                'id'           => 12,
                'branch_id'    => $jkt?->id,
                'machine_name' => 'E-Beam IS1020',
                'slug'         => 'e-beam-is1020',
                'location'     => 'BRIN Lebak Bulus'
            ],
            [
                'id'           => 13,
                'branch_id'    => $jkt?->id,
                'machine_name' => 'Airport Soetta',
                'slug'         => 'airport-soetta',
                'location'     => 'Airport Soekarno-Hatta Jakarta'
            ],
            [
                'id'           => 14,
                'branch_id'    => $jkt?->id,
                'machine_name' => 'Software Division',
                'slug'         => 'software-division',
                'location'     => '--'
            ],
            [
                'id'           => 15,
                'branch_id'    => $dps?->id,
                'machine_name' => 'CTMIC2100-YW Bali',
                'slug'         => 'ctmic2100-yw-bali',
                'location'     => '--'
            ],
            [
                'id'           => 16,
                'branch_id'    => $bwx?->id,
                'machine_name' => 'CTMIC2100-YW Banyuwangi',
                'slug'         => 'ctmic2100-yw-banyuwangi',
                'location'     => '--'
            ],
        ];

        foreach ($sites as $site) {
            if ($site['branch_id']) {
                Site::updateOrCreate(
                    ['id' => $site['id']],
                    $site
                );
            }
        }
    }
}
