<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Site;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // $userData = [
        //     [
        //         'name' => 'user',
        //         'username' => 'user',
        //         'email' => 'user@gmail.com',
        //         'role' => 'user',
        //         'password' => bcrypt('123456'),
        //     ],
        //     [
        //         'name' => 'admin',
        //         'username' => 'admin',
        //         'email' => 'admin@gmail.com',
        //         'role' => 'admin',
        //         'password' => bcrypt('123456'),
        //     ],
        // ];

        // foreach ($userData as $key => $val) {
        //     User::create($val);
        // }
        // Superadmin tetap null
        User::create([
            'username' => 'pusat_admin',
            'name'     => 'Super Admin Nuctech',
            'email'    => 'admin@nuctech.com',
            'role'     => 'superadmin',
            'password' => Hash::make('password123'),
            'site_id'  => null,
        ]);

        // Ambil Site Surabaya dari database
        $siteSby = Site::where('machine_name', 'LIKE', '%SBY%')->first();

        if ($siteSby) {
            User::create([
                'username' => 'sby_admin',
                'name'     => 'Admin SBY',
                'email'    => 'sby@nuctech.com',
                'role'     => 'admin_site',
                'password' => Hash::make('password123'),
                'site_id'  => $siteSby->id, // Gunakan ID hasil pencarian
            ]);
        }
    }
}
