<?php

declare(strict_types=1);

namespace Modules\Order\Application\Handlers;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Order\Application\Queries\GetPaidOrdersQuery;
use Modules\Order\Domain\Enums\OrderStatus;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;

final class GetPaidOrdersHandler
{
    public function handle(GetPaidOrdersQuery $query): LengthAwarePaginator
    {
        return OrderModel::with(['items.product'])
            ->where('status', OrderStatus::PAID->value)
            ->orderByDesc('created_at')
            ->paginate(15);
    }
}
