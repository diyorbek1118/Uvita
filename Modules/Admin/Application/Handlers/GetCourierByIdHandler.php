<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Handlers;

use Modules\Admin\Application\Queries\GetCourierByIdQuery;
use Modules\Admin\Domain\Enums\StaffRole;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;

final class GetCourierByIdHandler
{
    public function handle(GetCourierByIdQuery $query): Staff
    {
        $courier = Staff::where('role', StaffRole::COURIER)->findOrFail($query->courierId);

        $delivered   = OrderModel::where('courier_id', $courier->id)->where('status', 'delivered')->count();
        $notFound    = (int) OrderModel::where('courier_id', $courier->id)->sum('not_found_count');
        $active      = OrderModel::where('courier_id', $courier->id)->whereIn('status', ['delivering', 'ready_to_deliver'])->count();
        $total       = $delivered + $notFound;
        $successRate = $total > 0 ? round($delivered / $total * 100, 1) : 0.0;

        $recentDeliveries = OrderModel::where('courier_id', $courier->id)
            ->where('status', 'delivered')
            ->latest()
            ->take(10)
            ->get(['id', 'address', 'grand_total', 'created_at']);

        $courier->setAttribute('total_delivered', $delivered);
        $courier->setAttribute('total_not_found', $notFound);
        $courier->setAttribute('total_active', $active);
        $courier->setAttribute('success_rate', $successRate);
        $courier->setAttribute('recent_deliveries', $recentDeliveries);

        return $courier;
    }
}
