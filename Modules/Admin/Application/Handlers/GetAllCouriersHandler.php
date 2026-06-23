<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Handlers;

use Illuminate\Database\Eloquent\Collection;
use Modules\Admin\Domain\Enums\StaffRole;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;

final class GetAllCouriersHandler
{
    public function handle(): Collection
    {
        $couriers = Staff::where('role', StaffRole::COURIER)->get();

        foreach ($couriers as $courier) {
            $delivered   = OrderModel::where('courier_id', $courier->id)->where('status', 'delivered')->count();
            $notFound    = (int) OrderModel::where('courier_id', $courier->id)->sum('not_found_count');
            $active      = OrderModel::where('courier_id', $courier->id)->whereIn('status', ['delivering', 'ready_to_deliver'])->count();
            $total       = $delivered + $notFound;
            $successRate = $total > 0 ? round($delivered / $total * 100, 1) : 0.0;

            $courier->setAttribute('total_delivered', $delivered);
            $courier->setAttribute('total_not_found', $notFound);
            $courier->setAttribute('total_active', $active);
            $courier->setAttribute('success_rate', $successRate);
        }

        return $couriers;
    }
}
