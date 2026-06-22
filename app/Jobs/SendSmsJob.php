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

    public function __construct(
        public readonly string $phone,
        public readonly string $message,
    ) {}

    public function handle(SmsService $smsService): void
    {
        $smsService->send($this->phone, $this->message);
    }
}
