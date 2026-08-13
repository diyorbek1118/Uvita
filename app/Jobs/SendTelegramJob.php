<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Shared\Services\Telegram\TelegramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

final class SendTelegramJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    /** @var int[] */
    public array $backoff = [10, 60, 300];

    public function __construct(
        public readonly string $role,
        public readonly string $message,
    ) {}

    public function handle(TelegramService $telegramService): void
    {
        $sent = match ($this->role) {
            'admin' => $telegramService->sendToAdmin($this->message),
            'courier' => $telegramService->sendToCourier($this->message),
            default => $telegramService->sendToManager($this->message),
        };

        if (! $sent && (bool) config('telegram.required', false)) {
            throw new RuntimeException("Telegram xabari '{$this->role}' roli uchun yuborilmadi.");
        }
    }
}
