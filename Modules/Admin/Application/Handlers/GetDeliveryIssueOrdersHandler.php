<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Handlers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Admin\Application\Queries\GetDeliveryIssueOrdersQuery;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;

final class GetDeliveryIssueOrdersHandler
{
    public function handle(GetDeliveryIssueOrdersQuery $query): LengthAwarePaginator
    {
        return OrderModel::where('status', 'delivery_issue')
            ->with(['items.product', 'user'])
            ->latest()
            ->paginate(20);
    }
}
