<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Report;

class ReportSeeder extends Seeder
{
    public function run(): void
    {
        $reports = [
        ];

        foreach ($reports as $report) {
            Report::create($report);
        }
    }
}
