<?php

declare(strict_types=1);

namespace Modules\Courier\Application\Handlers;

use Modules\Courier\Infrastructure\Persistence\Models\CourierDevice;

final class RemoveCourierDeviceHandler
{
    public function handle(int $deviceId, int $courierId): void
    {
        CourierDevice::query()
            ->where('id', $deviceId)
            ->where('courier_id', $courierId)
            ->firstOrFail()
            ->delete();
    }
}
