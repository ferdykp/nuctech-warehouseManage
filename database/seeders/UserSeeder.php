<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Never create accounts with shared credentials, including in production.
        $this->command?->info('Buat admin dengan: php artisan security:create-admin');
    }
}
