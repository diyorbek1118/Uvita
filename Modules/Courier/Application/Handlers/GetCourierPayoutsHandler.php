<?php

declare(strict_types=1);

namespace Modules\Courier\Application\Handlers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Courier\Infrastructure\Persistence\Models\CourierPayout;

final class GetCourierPayoutsHandler
{
    public function handle(?int $courierId, ?string $status): LengthAwarePaginator
    {
        return CourierPayout::query()
            ->when($courierId, fn ($query) => $query->where('courier_id', $courierId))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(20);
    }
}
