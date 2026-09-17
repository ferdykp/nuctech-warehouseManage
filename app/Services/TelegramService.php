<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    /**
     * Kirim pesan ke Chat ID spesifik (balasan Webhook/Prompt)
     */
    public static function sendMessageToChat($chatId, string $message): bool
    {
        $token = config('services.telegram.bot_token');

        if (!$token) {
            Log::warning("Telegram Bot Token belum dikonfigurasi.");
            return false;
        }

        try {
            $response = Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id'                  => $chatId,
                'text'                     => $message,
                'parse_mode'               => 'HTML',
                'disable_web_page_preview' => true,
            ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error("Gagal mengirim pesan Telegram: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Kirim pesan default ke Chat ID utama di .env (misal untuk Error Alert)
     */
    public static function sendMessage(string $message): bool
    {
        $defaultChatId = config('services.telegram.chat_id');
        return self::sendMessageToChat($defaultChatId, $message);
    }
}
