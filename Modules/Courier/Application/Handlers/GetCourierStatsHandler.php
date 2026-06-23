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

        $totalNotFound = (int) OrderModel::where('courier_id', $id)
            ->sum('not_found_count');

        $totalActive = OrderModel::where('courier_id', $id)
            ->whereIn('status', ['delivering', 'ready_to_deliver'])
            ->count();

        return new CourierStats(
            totalDelivered: $totalDelivered,
            totalNotFound:  $totalNotFound,
            totalActive:    $totalActive,
        );
    }
}
