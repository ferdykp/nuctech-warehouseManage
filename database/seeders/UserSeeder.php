<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userData = [
            [
                'name' => 'user',
                'username' => 'user',
                'email' => 'user@gmail.com',
                'role' => 'user',
                'password' => bcrypt('123456'),
            ],
            [
                'name' => 'admin',
                'username' => 'admin',
                'email' => 'admin@gmail.com',
                'role' => 'admin',
                'password' => bcrypt('123456'),
            ],
        ];

        foreach ($userData as $key => $val) {
            User::create($val);
        }
    }
}
