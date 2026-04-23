<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Branch;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $branches = [
            ['branch_name' => 'Tanjung Priok', 'branch_code' => 'TPK', 'branch_address' => 'Jl. Pelabuhan Raya, Jakarta Utara'],
            ['branch_name' => 'Tanjung Perak', 'branch_code' => 'TPR', 'branch_address' => 'Jl. Perak Timur, Surabaya'],
            ['branch_name' => 'Belawan', 'branch_code' => 'BLW', 'branch_address' => 'Jl. Sumatera, Medan'],
        ];

        foreach ($branches as $branch) {
            Branch::create($branch);
        }
    }
}
