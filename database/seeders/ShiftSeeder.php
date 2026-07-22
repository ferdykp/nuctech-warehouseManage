<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Shift;

class ShiftSeeder extends Seeder
{
    public function run(): void
    {
        // Pastikan jika OFF, berikan nilai string jam kosong atau sesuaikan dengan is_off
        Shift::insert([
            [
                'shift_name' => 'Office Hour',
                'start_time' => '08:00:00',
                'end_time' => '16:00:00',
                'is_off' => false
            ],
            [
                'shift_name' => 'Shift 1',
                'start_time' => '08:00:00',
                'end_time' => '16:00:00',
                'is_off' => false
            ],
            [
                'shift_name' => 'Shift 2',
                'start_time' => '16:00:00',
                'end_time' => '00:00:00',
                'is_off' => false
            ],
            [
                'shift_name' => 'Shift 3',
                'start_time' => '00:00:00',
                'end_time' => '08:00:00',
                'is_off' => false
            ],
            // Gunakan 00:00:00 jika migration mewajibkan isi time, atau ubah migration menjadi nullable
            [
                'shift_name' => 'OFF',
                'start_time' => '00:00:00',
                'end_time' => '00:00:00',
                'is_off' => true
            ],
        ]);
    }
}
