<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Branch;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $branches = [
            ['branch_name' => 'Jakarta', 'branch_code' => 'JKT', 'branch_address' => '--'],
            ['branch_name' => 'Surabaya', 'branch_code' => 'SBY', 'branch_address' => '--'],
            ['branch_name' => 'Semarang', 'branch_code' => 'SMG', 'branch_address' => '--'],
        ];

        foreach ($branches as $branch) {
            Branch::create($branch);
        }
    }
}
