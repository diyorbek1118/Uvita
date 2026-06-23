<?php

declare(strict_types=1);

namespace Modules\Order\Application\Handlers;

use Modules\Order\Infrastructure\Persistence\Models\OrderModel;

final class GetAnyOrderByIdHandler
{
    public function handle(int $orderId): OrderModel
    {
        return OrderModel::with(['items.product'])->findOrFail($orderId);
    }
}
