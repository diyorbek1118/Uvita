<?php

declare(strict_types=1);

namespace Modules\Courier\Application\Handlers;

use Modules\Courier\Infrastructure\Persistence\Models\CourierLocation;

final class GetLatestCourierLocationHandler
{
    public function handle(int $courierId): CourierLocation
    {
        return CourierLocation::query()
            ->where('courier_id', $courierId)
            ->latest('recorded_at')
            ->firstOrFail();
    }
}
