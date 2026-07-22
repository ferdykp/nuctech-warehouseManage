<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\Site;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ambil beberapa site yang ada di database
        $ebeam  = Site::where('slug', 'ebeam-1')->first();
        $jkt    = Site::where('slug', 'fs6000jkt-1')->first();
        $sby    = Site::where('slug', 'fs6000sby-1')->first();
        $smg    = Site::where('slug', 'fs6000smg-1')->first();

        // Data dummy karyawan
        $employees = [
            [
                'site'                => $ebeam,
                'name'                => 'Budi Santoso',
                'phone_number'        => '081234567890',
                'position'            => 'Technician',
                'status'              => 'Permanent',
                'join_date'           => '2023-01-15',
                'contract_start_date' => '2023-01-15',
            ],
            [
                'site'                => $jkt,
                'name'                => 'Siti Rahma',
                'phone_number'        => '082198765432',
                'position'            => 'Supervisor',
                'status'              => 'Permanent',
                'join_date'           => '2022-05-10',
                'contract_start_date' => '2022-05-10',
            ],
            [
                'site'                => $sby,
                'name'                => 'Andi Wijaya',
                'phone_number'        => '085711223344',
                'position'            => 'Operator',
                'status'              => 'Contract',
                'join_date'           => '2024-02-01',
                'contract_start_date' => '2024-02-01',
            ],
            [
                'site'                => $smg,
                'name'                => 'Dewi Lestari',
                'phone_number'        => '089655443322',
                'position'            => 'Admin',
                'status'              => 'Probation',
                'join_date'           => '2024-06-01',
                'contract_start_date' => null,
            ],
        ];

        foreach ($employees as $data) {
            // Pastikan site ditemukan sebelum membuat data employee
            if ($data['site']) {
                Employee::create([
                    'site_id'             => $data['site']->id,
                    'branch_id'           => $data['site']->branch_id, // Mengambil otomatis dari relasi site
                    'name'                => $data['name'],
                    'phone_number'        => $data['phone_number'],
                    'position'            => $data['position'],
                    'status'              => $data['status'],
                    'join_date'           => $data['join_date'],
                    'contract_start_date' => $data['contract_start_date'],
                    'is_active'           => true,
                ]);
            }
        }
    }
}
