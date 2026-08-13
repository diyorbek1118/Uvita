<?php

declare(strict_types=1);

namespace Modules\Courier\Infrastructure\Services;

use App\Jobs\SendCourierPushJob;
use Modules\Courier\Application\Contracts\CourierNotifierInterface;
use Modules\Courier\Infrastructure\Persistence\Models\CourierNotification;

final class EloquentCourierNotifier implements CourierNotifierInterface
{
    public function notify(int $courierId, string $type, string $title, string $body, array $data = []): void
    {
        CourierNotification::create([
            'courier_id' => $courierId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);

        dispatch(new SendCourierPushJob($courierId, $title, $body, $data))->afterCommit();
    }
}
