<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Handlers;

use App\Shared\Services\Fee\OrderFeeCalculator;
use Modules\Admin\Application\Queries\GetDashboardOrderDetailQuery;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;

final class GetDashboardOrderDetailHandler
{
    public function __construct(
        private readonly OrderFeeCalculator $feeCalculator,
    ) {}

    public function handle(GetDashboardOrderDetailQuery $query): OrderModel
    {
        $builder = OrderModel::query()
            ->with(['items.product', 'user', 'courier'])
            ->where('id', $query->id);

        if ($query->managerScope) {
            $builder->whereIn('status', GetDashboardOrdersHandler::MANAGER_VISIBLE);
        }

        $order = $builder->firstOrFail();

        // Narx breakdown (mahsulotlar summasi = total_price'dan) — resource rolga qarab ko'rsatadi.
        $order->setAttribute(
            'financials',
            $this->feeCalculator->calculate((int) $order->total_price)->toArray()
        );

        return $order;
    }
}
