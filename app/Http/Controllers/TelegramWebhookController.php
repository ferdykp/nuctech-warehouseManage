<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $update = $request->all();

        if (!isset($update['message']['text'])) {
            return response()->json(['status' => 'ok']);
        }

        $text = trim($update['message']['text']);
        $chatId = $update['message']['chat']['id'];

        if (str_starts_with($text, '/report')) {
            $this->sendWebReportTemplate($chatId);
        } elseif (str_starts_with($text, '/log')) {
            $this->sendLogAndBugStatus($chatId);
        } elseif (str_starts_with($text, '/clearlog')) {
            $this->clearSystemLog($chatId);
        } elseif (str_starts_with($text, '/backup')) {
            $this->backupDatabaseToTelegram($chatId);
        } elseif (str_starts_with($text, '/start') || str_starts_with($text, '/help')) {
            $reply = "👋 <b>System Monitoring Bot Ready!</b>\n\n";
            $reply .= "Gunakan perintah berikut:\n";
            $reply .= "• <b>/report</b> : Template Laporan Harian Web + Auto Changelog Git (Siap Copas)\n";
            $reply .= "• <b>/log</b> : Cek error log Laravel & kapasitas disk server\n";
            $reply .= "• <b>/clearlog</b> : Bersihkan isi file laravel.log\n";
            $reply .= "• <b>/backup</b> : Backup Database (.sql) & kirim filenya ke Telegram";

            TelegramService::sendMessageToChat($chatId, $reply);
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Menampilkan Template Laporan Harian Web dengan Auto Changelog dari Git
     */
    private function sendWebReportTemplate($chatId)
    {
        $dateNow = Carbon::now()->translatedFormat('l, d F Y');
        $timeNow = Carbon::now()->format('H:i');

        // Cek status database
        $dbStatus = 'Online / Stable';
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            $dbStatus = 'Error Connection!';
        }

        // Cek error log hari ini
        $logPath = storage_path('logs/laravel.log');
        $hasErrorToday = false;
        if (File::exists($logPath)) {
            $todayStr = Carbon::today()->format('Y-m-d');
            $logContent = File::get($logPath);
            if (str_contains($logContent, "[$todayStr]") && str_contains($logContent, 'ERROR')) {
                $hasErrorToday = true;
            }
        }
        $systemHealth = $hasErrorToday ? '⚠️ Ada Warning/Error Log' : '✅ Clean / Normal';

        // AMBIL AUTO CHANGELOG DARI GIT LOG HARI INI
        $gitCommits = [];
        try {
            // Ambil commit git sejak jam 00:00 hari ini
            $command = 'git log --since="midnight" --pretty=format:"%s"';
            $output = shell_exec($command);
            if (!empty($output)) {
                $gitCommits = array_filter(explode("\n", trim($output)));
            }
        } catch (\Exception $e) {
            Log::warning("Gagal membaca git log: " . $e->getMessage());
        }

        // Format string Changelog
        $changelogStr = "";
        if (!empty($gitCommits)) {
            foreach ($gitCommits as $commitMsg) {
                $changelogStr .= "   • " . e($commitMsg) . "\n";
            }
        } else {
            $changelogStr = "   • Tidak ada update kode / deployment hari ini.\n";
        }

        // Template siap copas
        $msg = "LAPORAN HARIAN SISTEM WEB\n";
        $msg .= "Tanggal : {$dateNow}\n";
        $msg .= "Waktu   : {$timeNow} WIB\n\n";
        $msg .= "1. STATUS SISTEM & SERVER\n";
        $msg .= "   • Status Web App : Online\n";
        $msg .= "   • Database Status : {$dbStatus}\n";
        $msg .= "   • System Health  : {$systemHealth}\n\n";
        $msg .= "2. PERBAIKAN / UPDATE HARI INI (CHANGELOG)\n";
        $msg .= $changelogStr . "\n";
        $msg .= "3. ISU TEKNIS & BUG LOG\n";
        $msg .= "   • [Status Bug] : " . ($hasErrorToday ? "Ditemukan error pada log hari ini (Cek via /log)." : "Tidak ada isu kritis hari ini.") . "\n\n";
        $msg .= "4. CATATAN / RENCANA BESOK\n";
        $msg .= "   • Monitoring berkala & pemantauan pengguna site.\n";

        TelegramService::sendMessageToChat($chatId, $msg);
    }

    /**
     * Menampilkan Detail Error Log Laravel & Cek Disk
     */
    private function sendLogAndBugStatus($chatId)
    {
        $logPath = storage_path('logs/laravel.log');

        $msg = "🔍 <b>SYSTEM LOG & BUG CHECKER</b>\n";
        $msg .= "Waktu Cek: " . Carbon::now()->format('d/m/Y H:i:s') . " WIB\n";
        $msg .= "--------------------------------------------------\n\n";

        if (!File::exists($logPath) || File::size($logPath) === 0) {
            $msg .= "🟢 <b>Log File:</b> File log kosong (Clean).\n";
        } else {
            $fileLines = file($logPath);
            $lastLines = array_slice($fileLines, -15);
            $logSnippet = implode("", $lastLines);

            if (str_contains(strtoupper($logSnippet), 'ERROR') || str_contains(strtoupper($logSnippet), 'EXCEPTION')) {
                $msg .= "🔴 <b>Status Log:</b> Terdeteksi ERROR/EXCEPTION!\n\n";
                $msg .= "<b>Potongan Log Terakhir:</b>\n";
                $msg .= "<pre>" . e(substr($logSnippet, 0, 1000)) . "</pre>\n";
            } else {
                $msg .= "🟢 <b>Status Log:</b> Normal / Tidak ada error kritis pada baris terakhir.\n\n";
                $msg .= "<b>Log Terakhir:</b>\n";
                $msg .= "<pre>" . e(substr($logSnippet, 0, 500)) . "</pre>\n";
            }
        }

        $freeSpace = round(disk_free_space("/") / (1024 * 1024 * 1024), 2);
        $totalSpace = round(disk_total_space("/") / (1024 * 1024 * 1024), 2);

        $msg .= "\n💾 <b>Kapasitas Disk Server:</b>\n";
        $msg .= "• Sisa Storage: <b>{$freeSpace} GB</b> dari {$totalSpace} GB\n";

        TelegramService::sendMessageToChat($chatId, $msg);
    }

    /**
     * Membersihkan File Log Laravel (/clearlog)
     */
    private function clearSystemLog($chatId)
    {
        $logPath = storage_path('logs/laravel.log');

        if (File::exists($logPath)) {
            File::put($logPath, ''); // Kosongkan file log
            $msg = "🧹 <b>SUCCESS!</b> File <code>laravel.log</code> berhasil dibersihkan.";
        } else {
            $msg = "ℹ️ File log sudah dalam keadaan kosong.";
        }

        TelegramService::sendMessageToChat($chatId, $msg);
    }

    /**
     * Backup Database (.sql) dan Kirim File ke Telegram (/backup)
     */
    private function backupDatabaseToTelegram($chatId)
    {
        TelegramService::sendMessageToChat($chatId, "⏳ <i>Memproses dump database, mohon tunggu sebentar...</i>");

        try {
            $dbName = env('DB_DATABASE');
            $dbUser = env('DB_USERNAME');
            $dbPassword = env('DB_PASSWORD');
            $dbHost = env('DB_HOST', '127.0.0.1');

            $fileName = 'backup_' . $dbName . '_' . date('Y-m-d_H-i-s') . '.sql';
            $filePath = storage_path('app/' . $fileName);

            // Perintah mysqldump
            if (!empty($dbPassword)) {
                $command = "mysqldump -h {$dbHost} -u {$dbUser} -p'{$dbPassword}' {$dbName} > {$filePath}";
            } else {
                $command = "mysqldump -h {$dbHost} -u {$dbUser} {$dbName} > {$filePath}";
            }

            exec($command, $output, $returnVar);

            if ($returnVar === 0 && File::exists($filePath)) {
                // Kirim Dokumen File SQL ke Telegram API
                $token = config('services.telegram.bot_token');

                $response = Http::attach(
                    'document',
                    file_get_contents($filePath),
                    $fileName
                )->post("https://api.telegram.org/bot{$token}/sendDocument", [
                    'chat_id' => $chatId,
                    'caption' => "📦 <b>BACKUP DATABASE SUCCESS</b>\nNama File: <code>{$fileName}</code>\nTanggal: " . now()->format('d/m/Y H:i') . " WIB"
                ]);

                // Hapus file backup lokal di server setelah terkirim agar storage hemat
                File::delete($filePath);

                if (!$response->successful()) {
                    TelegramService::sendMessageToChat($chatId, "❌ Gagal mengirim file backup ke Telegram: " . $response->body());
                }
            } else {
                TelegramService::sendMessageToChat($chatId, "❌ Gagal melakukan dump database. Pastikan `mysqldump` terinstall di server.");
            }
        } catch (\Exception $e) {
            TelegramService::sendMessageToChat($chatId, "❌ Terjadi error saat backup: " . $e->getMessage());
        }
    }
}
