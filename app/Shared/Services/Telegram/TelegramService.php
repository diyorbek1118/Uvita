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
        $this->apiUrl = (string) config('telegram.api_url', 'https://api.telegram.org/bot');
    }

    public function send(string $chatId, string $message): void
    {
        if ($this->token === '' || $chatId === '') {
            Log::warning("TelegramService: token yoki chatId bo'sh, xabar yuborilmadi.");
            return;
        }

        $response = Http::timeout(10)->post("{$this->apiUrl}{$this->token}/sendMessage", [
            'chat_id'    => $chatId,
            'text'       => $message,
            'parse_mode' => 'HTML',
        ]);

        if (!$response->successful()) {
            Log::error("TelegramService: xabar yuborishda xato.", [
                'chat_id'  => $chatId,
                'status'   => $response->status(),
                'response' => $response->body(),
            ]);
        }
    }
}
