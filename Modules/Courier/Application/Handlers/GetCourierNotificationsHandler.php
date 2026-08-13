<?php

declare(strict_types=1);

namespace Modules\Courier\Application\Handlers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Courier\Infrastructure\Persistence\Models\CourierNotification;

final class GetCourierNotificationsHandler
{
    public function handle(int $courierId): LengthAwarePaginator
    {
        return CourierNotification::query()
            ->where('courier_id', $courierId)
            ->latest()
            ->paginate(20);
    }
}
