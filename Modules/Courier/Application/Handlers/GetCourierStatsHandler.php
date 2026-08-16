<?php

declare(strict_types=1);

namespace Modules\Courier\Application\Handlers;

use Modules\Courier\Application\Queries\GetCourierStatsQuery;
use Modules\Courier\Domain\ValueObjects\CourierStats;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;

final class GetCourierStatsHandler
{
    public function handle(GetCourierStatsQuery $query): CourierStats
    {
        $id = $query->courierId;

        $totalDelivered = OrderModel::where('courier_id', $id)
            ->where('status', 'delivered')
            ->count();

        $todayDelivered = OrderModel::where('courier_id', $id)
            ->where('status', 'delivered')
            ->where(function ($builder): void {
                $builder->whereDate('delivered_at', today())
                    ->orWhere(function ($legacy): void {
                        $legacy->whereNull('delivered_at')->whereDate('updated_at', today());
                    });
            })
            ->count();

        $totalNotFound = (int) OrderModel::where('courier_id', $id)
            ->sum('not_found_count');

        $totalActive = OrderModel::where('courier_id', $id)
            ->whereIn('status', ['delivering', 'ready_to_deliver'])
            ->count();

        return new CourierStats(
            totalDelivered: $totalDelivered,
            todayDelivered: $todayDelivered,
            totalNotFound: $totalNotFound,
            totalActive: $totalActive,
        );
    }
}
