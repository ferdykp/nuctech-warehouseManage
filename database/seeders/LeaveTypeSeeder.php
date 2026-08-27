<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'name'             => 'Cuti Tahunan',
                'default_quota'    => 12,
                'is_paid'          => true, // Gaji Utuh
                'cut_annual_quota' => true,
                'requires_file'    => false,
            ],
            [
                'name'             => 'Cuti Sakit',
                'default_quota'    => 12,
                'is_paid'          => true, // Gaji Utuh
                'cut_annual_quota' => false,
                'requires_file'    => true, // Lampirkan Surat Dokter
            ],
            [
                'name'             => 'Izin Khusus (Menikah / Duka)',
                'default_quota'    => 3,
                'is_paid'          => true, // Gaji Utuh
                'cut_annual_quota' => false,
                'requires_file'    => false,
            ],
            [
                'name'             => 'Izin Alasan Penting',
                'default_quota'    => 5,
                'is_paid'          => true, // Gaji Utuh
                'cut_annual_quota' => false,
                'requires_file'    => false,
            ],
        ];

        foreach ($types as $type) {
            LeaveType::updateOrCreate(['name' => $type['name']], $type);
        }
    }
}
