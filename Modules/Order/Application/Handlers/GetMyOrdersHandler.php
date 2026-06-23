<?php

declare(strict_types=1);

namespace Modules\Order\Application\Handlers;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Order\Application\Queries\GetMyOrdersQuery;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;

final class GetMyOrdersHandler
{
    public function handle(GetMyOrdersQuery $query): LengthAwarePaginator
    {
        return OrderModel::with(['items.product'])
            ->where('user_id', $query->userId)
            ->orderByDesc('created_at')
            ->paginate(15);
    }
}
