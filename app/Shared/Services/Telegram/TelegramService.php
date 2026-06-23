<?php

declare(strict_types=1);

namespace App\Shared\Services\Telegram;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class TelegramService
{
    private string $token;
    private string $apiUrl;

    public function __construct()
    {
        $this->token  = (string) config('telegram.bot_token', '');
        $this->apiUrl = "https://api.telegram.org/bot{$this->token}";
    }

    public function send(string $chatId, string $message): bool
    {
        if (empty($this->token) || empty($chatId)) {
            Log::warning("Telegram: token yoki chatId bo'sh", [
                'chat_id' => $chatId,
            ]);
            return false;
        }

        try {
            $response = Http::timeout(10)->post("{$this->apiUrl}/sendMessage", [
                'chat_id'    => $chatId,
                'text'       => $message,
                'parse_mode' => 'HTML',
            ]);

            if (!$response->successful()) {
                Log::error('Telegram xato', [
                    'chat_id'  => $chatId,
                    'response' => $response->json(),
                ]);
                return false;
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Telegram exception', [
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function sendToManager(string $message): void
    {
        $this->sendToMany(
            config('telegram.chat_ids.manager', []),
            $message
        );
    }

    public function sendToAdmin(string $message): void
    {
        $this->sendToMany(
            config('telegram.chat_ids.admin', []),
            $message
        );
    }

    public function sendToCourier(string $message): void
    {
        $this->sendToMany(
            config('telegram.chat_ids.courier', []),
            $message
        );
    }

    private function sendToMany(array $chatIds, string $message): void
    {
        if (empty($chatIds)) {
            Log::info("Telegram: chat_ids bo'sh, xabar yuborilmadi");
            return;
        }

        foreach ($chatIds as $chatId) {
            $this->send((string) $chatId, $message);
        }
    }
}
