<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Site;
use App\Models\User;
use App\Models\DailyReport;
use App\Models\DailyReportPhoto;
use Carbon\Carbon;

class DailyReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ambil User Pengirim Default (misal user ID 1 atau superadmin/admin)
        $user = User::first() ?? User::factory()->create([
            'name' => 'System Operator',
            'email' => 'operator@company.com',
            'role' => 'superadmin',
        ]);

        // 2. Ambil Semua Site yang Ada di Database
        $sites = Site::all();

        if ($sites->isEmpty()) {
            $this->command->warn('Tidak ada Site yang ditemukan. Harap jalankan MachineSiteAndBranchSeeder terlebih dahulu!');
            return;
        }

        // Template Catatan Log Harian Realistis
        $logTemplates = [
            "Import scanner status : Working normally\nDown Time : 0\nSpare Part Consume : None\n\nExport scanner status : Working normally\nDown Time : 0\nSpare Part Consume : None",
            "Routine maintenance and calibration completed successfully.\nCleaned optical lenses and detector sensors.\nAll systems operational.",
            "System check complete.\nMinor alignment adjustment made on conveyor belt #2.\nNo downtime recorded.",
            "Operational check passed.\nGenerator stability verified.\nEnvironment temperature: 24°C (Normal).",
            "Daily inspection conducted.\nReplaced air filter unit.\nEquipment performing within optimal parameters.",
        ];

        // 3. Generate Laporan Harian untuk Setiap Site
        foreach ($sites as $site) {
            // Setiap site diberi 3-5 laporan dalam rentang 7 hari terakhir
            for ($i = 6; $i >= 0; $i -= rand(1, 2)) {
                $reportDate = Carbon::now()->subDays($i);

                $description = $logTemplates[array_rand($logTemplates)];

                $report = DailyReport::create([
                    'site_id'     => $site->id,
                    'user_id'     => $user->id,
                    'report_date' => $reportDate->format('Y-m-d'),
                    'description' => "[$site->machine_name] $description",
                ]);

                // 4. Tambahkan 1-2 Foto Dokumentasi Dummy per Laporan
                $photoCount = rand(1, 2);
                for ($p = 1; $p <= $photoCount; $p++) {
                    DailyReportPhoto::create([
                        'daily_report_id' => $report->id,
                        'photo_path'      => 'daily_reports/sample_photo.jpg', // Path dummy
                        'caption'         => "Site Inspection Photo #$p - $site->machine_name",
                    ]);
                }
            }
        }

        $this->command->info("Daily Reports successfully seeded for {$sites->count()} sites!");
    }
}
