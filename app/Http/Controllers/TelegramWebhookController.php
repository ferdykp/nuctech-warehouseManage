<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
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
        } elseif (str_starts_with($text, '/start') || str_starts_with($text, '/help')) {
            $reply = "👋 <b>System Monitoring Bot Ready!</b>\n\n";
            $reply .= "Gunakan perintah berikut:\n";
            $reply .= "• <b>/report</b> : Template Laporan Harian Sistem Web (Siap Copas)\n";
            $reply .= "• <b>/log</b> : Cek error log Laravel & kesehatan server saat ini";

            TelegramService::sendMessageToChat($chatId, $reply);
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Menampilkan Template Laporan Harian Web (Tinggal Copas)
     */
    private function sendWebReportTemplate($chatId)
    {
        $dateNow = Carbon::now()->translatedFormat('l, d F Y');
        $timeNow = Carbon::now()->format('H:i');

        // Cek status database secara langsung
        $dbStatus = 'Online / Stable';
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            $dbStatus = 'Error Connection!';
        }

        // Cek apakah ada error di log hari ini
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

        // Template siap copas
        $msg = "<code>==================================\n";
        $msg .= "LAPORAN HARIAN SISTEM WEB\n";
        $msg .= "Tanggal : {$dateNow}\n";
        $msg .= "Waktu   : {$timeNow} WIB\n";
        $msg .= "==================================\n\n";
        $msg .= "1. STATUS SISTEM & SERVER\n";
        $msg .= "   • Status Web App : Online\n";
        $msg .= "   • Database Status : {$dbStatus}\n";
        $msg .= "   • System Health  : {$systemHealth}\n\n";
        $msg .= "2. PERBAIKAN / UPDATE HARI INI (CHANGELOG)\n";
        $msg .= "   • [Fitur/Fix] : - \n";
        $msg .= "   • [Fitur/Fix] : - \n\n";
        $msg .= "3. ISU TEKNIS & BUG LOG\n";
        $msg .= "   • [Status Bug] : Tidak ada isu kritis hari ini.\n\n";
        $msg .= "4. CATATAN / RENCANA BESOK\n";
        $msg .= "   • Monitoring berkala & optimasi performa.\n";
        $msg .= "==================================</code>";

        TelegramService::sendMessageToChat($chatId, $msg);
    }

    /**
     * Menampilkan Detail Error Log Laravel & Kesehatan Storage
     */
    private function sendLogAndBugStatus($chatId)
    {
        $logPath = storage_path('logs/laravel.log');

        $msg = "🔍 <b>SYSTEM LOG & BUG CHECKER</b>\n";
        $msg .= "Waktu Cek: " . Carbon::now()->format('d/m/Y H:i:s') . " WIB\n";
        $msg .= "--------------------------------------------------\n\n";

        if (!File::exists($logPath)) {
            $msg .= "🟢 <b>Log File:</b> Tidak ditemukan file log (Clean).\n";
        } else {
            // Ambil 15 baris terakhir dari log
            $fileLines = file($logPath);
            $lastLines = array_slice($fileLines, -15);
            $logSnippet = implode("", $lastLines);

            // Cek indikator error
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

        // Cek kapasitas storage server
        $freeSpace = round(disk_free_space("/") / (1024 * 1024 * 1024), 2);
        $totalSpace = round(disk_total_space("/") / (1024 * 1024 * 1024), 2);

        $msg .= "\n💾 <b>Kapasitas Disk Server:</b>\n";
        $msg .= "• Sisa Storage: <b>{$freeSpace} GB</b> dari {$totalSpace} GB\n";

        TelegramService::sendMessageToChat($chatId, $msg);
    }
}
