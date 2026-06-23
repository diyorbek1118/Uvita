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
        public readonly string $role,
        public readonly string $message,
    ) {}

    public function handle(TelegramService $telegramService): void
    {
        match ($this->role) {
            'admin'   => $telegramService->sendToAdmin($this->message),
            'courier' => $telegramService->sendToCourier($this->message),
            default   => $telegramService->sendToManager($this->message),
        };
    }
}
