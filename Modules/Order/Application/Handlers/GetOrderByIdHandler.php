<?php

declare(strict_types=1);

namespace Modules\Order\Application\Handlers;

use Modules\Order\Application\Queries\GetOrderByIdQuery;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;

final class GetOrderByIdHandler
{
    public function handle(GetOrderByIdQuery $query): OrderModel
    {
        return OrderModel::with(['items.product'])
            ->where('id', $query->orderId)
            ->where('user_id', $query->userId)
            ->firstOrFail();
    }
}
