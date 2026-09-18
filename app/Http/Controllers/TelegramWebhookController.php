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
            $reply .= "Use the following commands:\n";
            $reply .= "• <b>/report</b> : Daily Web Report Template + Git Auto Changelog (Copy-paste ready)\n";
            $reply .= "• <b>/log</b> : Check Laravel error logs & server disk capacity\n";
            $reply .= "• <b>/clearlog</b> : Clear laravel.log file content\n";
            $reply .= "• <b>/backup</b> : Backup Database (.sql) & send file to Telegram";

            TelegramService::sendMessageToChat($chatId, $reply);
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Display Daily Web Report Template with Auto Changelog from Git
     */
    private function sendWebReportTemplate($chatId)
    {
        $dateNow = Carbon::now()->format('l, F d, Y');
        $timeNow = Carbon::now()->format('H:i');

        // Check database status
        $dbStatus = 'Online / Stable';
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            $dbStatus = 'Connection Error!';
        }

        // Check error log today
        $logPath = storage_path('logs/laravel.log');
        $hasErrorToday = false;
        if (File::exists($logPath)) {
            $todayStr = Carbon::today()->format('Y-m-d');
            $logContent = File::get($logPath);
            if (str_contains($logContent, "[$todayStr]") && str_contains($logContent, 'ERROR')) {
                $hasErrorToday = true;
            }
        }
        $systemHealth = $hasErrorToday ? '⚠️ Warning / Error Log Found' : '✅ Clean / Normal';

        // FETCH AUTO CHANGELOG FROM GIT LOG TODAY
        $gitCommits = [];
        try {
            // Fetch git commits since midnight today
            $command = 'git log --since="midnight" --pretty=format:"%s"';
            $output = shell_exec($command);
            if (!empty($output)) {
                $gitCommits = array_filter(explode("\n", trim($output)));
            }
        } catch (\Exception $e) {
            Log::warning("Failed to read git log: " . $e->getMessage());
        }

        // Format Changelog string
        $changelogStr = "";
        if (!empty($gitCommits)) {
            foreach ($gitCommits as $commitMsg) {
                $changelogStr .= "   • " . e($commitMsg) . "\n";
            }
        } else {
            $changelogStr = "   • No code updates / deployments today.\n";
        }

        // Ready copy-paste template
        $msg = "DAILY WEB SYSTEM REPORT\n";
        $msg .= "Date : {$dateNow}\n";
        $msg .= "Time : {$timeNow} WIB\n\n";
        $msg .= "1. SYSTEM & SERVER STATUS\n";
        $msg .= "   • Web App Status  : Online\n";
        $msg .= "   • Database Status : {$dbStatus}\n";
        $msg .= "   • System Health   : {$systemHealth}\n\n";
        $msg .= "2. TODAY'S UPDATES & FIXES (CHANGELOG)\n";
        $msg .= $changelogStr . "\n";
        $msg .= "3. TECHNICAL ISSUES & BUG LOG\n";
        $msg .= "   • [Bug Status] : " . ($hasErrorToday ? "Errors detected in today's log (Check via /log)." : "No critical issues today.") . "\n\n";
        $msg .= "4. NOTES & PLAN FOR TOMORROW\n";
        $msg .= "   • Routine monitoring & site user supervision.\n";

        TelegramService::sendMessageToChat($chatId, $msg);
    }

    /**
     * Display Laravel Error Log Details & Disk Space Check
     */
    private function sendLogAndBugStatus($chatId)
    {
        $logPath = storage_path('logs/laravel.log');

        $msg = "🔍 <b>SYSTEM LOG & BUG CHECKER</b>\n";
        $msg .= "Check Time: " . Carbon::now()->format('Y-m-d H:i:s') . " WIB\n";
        $msg .= "--------------------------------------------------\n\n";

        if (!File::exists($logPath) || File::size($logPath) === 0) {
            $msg .= "🟢 <b>Log File:</b> Log file is empty (Clean).\n";
        } else {
            $fileLines = file($logPath);
            $lastLines = array_slice($fileLines, -15);
            $logSnippet = implode("", $lastLines);

            if (str_contains(strtoupper($logSnippet), 'ERROR') || str_contains(strtoupper($logSnippet), 'EXCEPTION')) {
                $msg .= "🔴 <b>Log Status:</b> ERROR/EXCEPTION Detected!\n\n";
                $msg .= "<b>Latest Log Snippet:</b>\n";
                $msg .= "<pre>" . e(substr($logSnippet, 0, 1000)) . "</pre>\n";
            } else {
                $msg .= "🟢 <b>Log Status:</b> Normal / No critical errors in latest lines.\n\n";
                $msg .= "<b>Latest Log:</b>\n";
                $msg .= "<pre>" . e(substr($logSnippet, 0, 500)) . "</pre>\n";
            }
        }

        $freeSpace = round(disk_free_space("/") / (1024 * 1024 * 1024), 2);
        $totalSpace = round(disk_total_space("/") / (1024 * 1024 * 1024), 2);

        $msg .= "\n💾 <b>Server Disk Capacity:</b>\n";
        $msg .= "• Free Storage: <b>{$freeSpace} GB</b> of {$totalSpace} GB\n";

        TelegramService::sendMessageToChat($chatId, $msg);
    }

    /**
     * Clear Laravel Log File (/clearlog)
     */
    private function clearSystemLog($chatId)
    {
        $logPath = storage_path('logs/laravel.log');

        if (File::exists($logPath)) {
            File::put($logPath, ''); // Empty log file$msg = "🧹 <b>SUCCESS!</b> File <code>laravel.log</code> has been cleared successfully.";
        } else {
            $msg = "ℹ️ Log file is already empty.";
        }

        TelegramService::sendMessageToChat($chatId, $msg);
    }

    /**
     * Backup Database (.sql) and Send File to Telegram (/backup)
     */
    private function backupDatabaseToTelegram($chatId)
    {
        TelegramService::sendMessageToChat($chatId, "⏳ <i>Processing database dump, please wait...</i>");

        try {
            $dbName = env('DB_DATABASE');
            $dbUser = env('DB_USERNAME');
            $dbPassword = env('DB_PASSWORD');
            $dbHost = env('DB_HOST', '127.0.0.1');

            $fileName = 'backup_' . $dbName . '_' . date('Y-m-d_H-i-s') . '.sql';
            $filePath = storage_path('app/' . $fileName);

            // mysqldump command
            if (!empty($dbPassword)) {
                $command = "mysqldump -h {$dbHost} -u {$dbUser} -p'{$dbPassword}' {$dbName} > {$filePath}";
            } else {
                $command = "mysqldump -h {$dbHost} -u {$dbUser} {$dbName} > {$filePath}";
            }

            exec($command, $output, $returnVar);

            if ($returnVar === 0 && File::exists($filePath)) {
                // Send SQL Document to Telegram API
                $token = config('services.telegram.bot_token');

                $response = Http::attach(
                    'document',
                    file_get_contents($filePath),
                    $fileName
                )->post("https://api.telegram.org/bot{$token}/sendDocument", [
                    'chat_id' => $chatId,
                    'caption' => "📦 <b>DATABASE BACKUP SUCCESSFUL</b>\nFile Name: <code>{$fileName}</code>\nDate: " . now()->format('Y-m-d H:i') . " WIB"
                ]);

                // Delete local backup file after sending to save storage
                File::delete($filePath);

                if (!$response->successful()) {
                    TelegramService::sendMessageToChat($chatId, "❌ Failed to send backup file to Telegram: " . $response->body());
                }
            } else {
                TelegramService::sendMessageToChat($chatId, "❌ Failed to dump database. Ensure `mysqldump` is installed on the server.");
            }
        } catch (\Exception $e) {
            TelegramService::sendMessageToChat($chatId, "❌ Error occurred during backup: " . $e->getMessage());
        }
    }
}
