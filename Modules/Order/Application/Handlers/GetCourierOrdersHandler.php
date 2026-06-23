<?php

declare(strict_types=1);

namespace Modules\Order\Application\Handlers;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Order\Application\Queries\GetCourierOrdersQuery;
use Modules\Order\Domain\Enums\OrderStatus;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;

final class GetCourierOrdersHandler
{
    public function handle(GetCourierOrdersQuery $query): LengthAwarePaginator
    {
        return OrderModel::with(['items.product'])
            ->where('courier_id', $query->courierId)
            ->whereIn('status', [
                OrderStatus::READY_TO_DELIVER->value,
                OrderStatus::DELIVERING->value,
            ])
            ->orderByDesc('created_at')
            ->paginate(15);
    }
}
