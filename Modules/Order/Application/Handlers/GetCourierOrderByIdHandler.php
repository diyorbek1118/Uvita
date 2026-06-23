<?php

declare(strict_types=1);

namespace Modules\Order\Application\Handlers;

use Modules\Order\Infrastructure\Persistence\Models\OrderModel;

final class GetCourierOrderByIdHandler
{
    public function handle(int $orderId, int $courierId): OrderModel
    {
        return OrderModel::with(['items.product'])
            ->where('id', $orderId)
            ->where('courier_id', $courierId)
            ->firstOrFail();
    }
}
