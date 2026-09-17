<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Site;
use App\Models\DailyReport;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Tangkap data yang dikirim Telegram
        $update = $request->all();

        if (!isset($update['message']['text'])) {
            return response()->json(['status' => 'ok']);
        }

        $text = trim($update['message']['text']);
        $chatId = $update['message']['chat']['id'];

        // Cek apakah perintahnya /report atau /report@nama_bot Anda
        if (str_starts_with($text, '/report')) {
            $this->sendDailyReportSummary($chatId);
        } elseif (str_starts_with($text, '/start') || str_starts_with($text, '/help')) {
            $reply = "👋 <b>Selamat datang di System Monitoring Bot!</b>\n\n";
            $reply .= "Gunakan perintah berikut:\n";
            $reply .= "• <b>/report</b> : Melihat ringkasan status input laporan site hari ini.";

            TelegramService::sendMessageToChat($chatId, $reply);
        }

        return response()->json(['status' => 'ok']);
    }

    private function sendDailyReportSummary($chatId)
    {
        $today = Carbon::today()->format('Y-m-d');
        $totalSites = Site::count();

        // Ambil ID site yang sudah menginput hari ini
        $activeSiteIds = DailyReport::whereDate('created_at', $today)
            ->pluck('site_id')
            ->unique();

        $totalActive = $activeSiteIds->count();
        $totalReports = DailyReport::whereDate('created_at', $today)->count();

        // Site yang belum input hari ini
        $missingSites = Site::whereNotIn('id', $activeSiteIds)->pluck('machine_name')->toArray();

        $msg = "📊 <b>LAPORAN HARIAN MASA TRIAL & ERROR</b>\n";
        $msg .= "Tanggal: " . Carbon::now()->translatedFormat('d F Y (H:i)') . " WIB\n";
        $msg .= "==================================\n\n";
        $msg .= "1. <b>STATUS PENGGUNAAN SITE:</b>\n";
        $msg .= "   • Total Site Aktif Input : <b>{$totalActive} dari {$totalSites} Site</b>\n";
        $msg .= "   • Total Laporan Masuk : <b>{$totalReports} Laporan</b>\n\n";

        if (!empty($missingSites)) {
            $msg .= "⚠️ <b>Site Belum Input Hari Ini (" . count($missingSites) . "):</b>\n";
            foreach ($missingSites as $siteName) {
                $msg .= "   • " . $siteName . "\n";
            }
        } else {
            $msg .= "🎉 <b>Luar biasa! Seluruh Site sudah menginput laporan hari ini.</b>\n";
        }

        TelegramService::sendMessageToChat($chatId, $msg);
    }
}
