<?php

declare(strict_types=1);

namespace Modules\Courier\Application\Handlers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Courier\Application\Queries\GetCourierHistoryQuery;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;

final class GetCourierHistoryHandler
{
    public function handle(GetCourierHistoryQuery $query): LengthAwarePaginator
    {
        return OrderModel::where('courier_id', $query->courierId)
            ->where('status', 'delivered')
            ->with(['items.product'])
            ->latest()
            ->paginate(15);
    }
}
