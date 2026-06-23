<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Shared\Services\Telegram\TelegramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class SendTelegramJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $chatId,
        public readonly string $message,
    ) {}

    public function handle(TelegramService $telegramService): void
    {
        $telegramService->send($this->chatId, $this->message);
    }
}
