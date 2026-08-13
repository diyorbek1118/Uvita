<?php

declare(strict_types=1);

namespace Modules\Courier\Application\Handlers;

use Modules\Courier\Infrastructure\Persistence\Models\CourierNotification;

final class MarkCourierNotificationReadHandler
{
    public function handle(int $notificationId, int $courierId): CourierNotification
    {
        $notification = CourierNotification::query()
            ->where('id', $notificationId)
            ->where('courier_id', $courierId)
            ->firstOrFail();
        $notification->update(['read_at' => $notification->read_at ?? now()]);

        return $notification->fresh();
    }
}
