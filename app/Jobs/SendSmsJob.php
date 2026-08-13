<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Shared\Services\SMS\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    /** @var int[] */
    public array $backoff = [10, 60, 300];

    public function __construct(
        public readonly string $phone,
        public readonly string $message,
    ) {}

    public function handle(SmsService $smsService): void
    {
        $smsService->send($this->phone, $this->message);
    }
}
