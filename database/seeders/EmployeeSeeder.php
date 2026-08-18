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

        // Data dummy karyawan lengkap dengan gaji dan rekening bank
        $employees = [
            [
                'site'                => $ebeam,
                'name'                => 'Budi Santoso',
                'phone_number'        => '+62 812-3456-7890',
                'position'            => 'Technician',
                'status'              => 'Permanent',
                'basic_salary'        => 6500000,
                'bank_name'           => 'BCA',
                'bank_account_number' => '1234567890',
                'join_date'           => '2023-01-15',
                'contract_start_date' => '2023-01-15',
            ],
            [
                'site'                => $jkt,
                'name'                => 'Siti Rahma',
                'phone_number'        => '+62 821-9876-5432',
                'position'            => 'Supervisor',
                'status'              => 'Permanent',
                'basic_salary'        => 8500000,
                'bank_name'           => 'Bank Mandiri',
                'bank_account_number' => '9876543210',
                'join_date'           => '2022-05-10',
                'contract_start_date' => '2022-05-10',
            ],
            [
                'site'                => $sby,
                'name'                => 'Andi Wijaya',
                'phone_number'        => '+62 857-1122-3344',
                'position'            => 'Operator',
                'status'              => 'Contract',
                'basic_salary'        => 5000000,
                'bank_name'           => 'BRI',
                'bank_account_number' => '1122334455',
                'join_date'           => '2024-02-01',
                'contract_start_date' => '2024-02-01',
            ],
            [
                'site'                => $smg,
                'name'                => 'Dewi Lestari',
                'phone_number'        => '+62 896-5544-3322',
                'position'            => 'Admin',
                'status'              => 'Probation',
                'basic_salary'        => 4500000,
                'bank_name'           => 'BNI',
                'bank_account_number' => '5544332211',
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
                    'basic_salary'        => $data['basic_salary'],
                    'bank_name'           => $data['bank_name'],
                    'bank_account_number' => $data['bank_account_number'],
                    'join_date'           => $data['join_date'],
                    'contract_start_date' => $data['contract_start_date'],
                    'is_active'           => true,
                ]);
            }
        }
    }
}
