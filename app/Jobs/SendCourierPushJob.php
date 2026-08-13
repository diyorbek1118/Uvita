<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Courier\Infrastructure\Persistence\Models\CourierDevice;
use Modules\Courier\Infrastructure\Services\FirebaseCloudMessagingService;

final class SendCourierPushJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @param array<string, mixed> $data */
    public function __construct(
        public readonly int $courierId,
        public readonly string $title,
        public readonly string $body,
        public readonly array $data = [],
    ) {}

    public function handle(FirebaseCloudMessagingService $firebase): void
    {
        CourierDevice::query()
            ->where('courier_id', $this->courierId)
            ->where('is_active', true)
            ->each(function (CourierDevice $device) use ($firebase): void {
                if ($firebase->send($device->token, $this->title, $this->body, $this->data)) {
                    $device->update(['last_used_at' => now()]);
                }
            });
    }
}
