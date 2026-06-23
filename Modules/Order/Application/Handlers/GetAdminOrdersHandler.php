<?php

declare(strict_types=1);

namespace Modules\Order\Application\Handlers;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Order\Application\Queries\GetAdminOrdersQuery;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;

final class GetAdminOrdersHandler
{
    public function handle(GetAdminOrdersQuery $query): LengthAwarePaginator
    {
        return OrderModel::with(['items.product'])
            ->orderByDesc('created_at')
            ->paginate(15);
    }
}
