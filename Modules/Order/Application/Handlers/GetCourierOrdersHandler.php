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
        return OrderModel::with(['items.product.sellerProfile', 'deliveryAssignments', 'deliveryAttempts', 'tripOrder.trip'])
            ->where('courier_id', $query->courierId)
            ->whereIn('status', [
                OrderStatus::READY_TO_DELIVER->value,
                OrderStatus::DELIVERING->value,
            ])
            ->orderByRaw("CASE WHEN status = 'delivering' THEN 0 ELSE 1 END")
            ->orderBy('delivery_time')
            ->paginate(15);
    }
}
