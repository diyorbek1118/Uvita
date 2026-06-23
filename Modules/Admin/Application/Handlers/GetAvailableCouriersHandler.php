<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Handlers;

use Illuminate\Database\Eloquent\Collection;
use Modules\Admin\Domain\Enums\StaffRole;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;

final class GetAvailableCouriersHandler
{
    public function handle(): Collection
    {
        $couriers = Staff::where('role', StaffRole::COURIER)
            ->where('is_active', true)
            ->get();

        foreach ($couriers as $courier) {
            $courier->setAttribute(
                'delivering_count',
                OrderModel::where('courier_id', $courier->id)
                    ->where('status', 'delivering')
                    ->count()
            );
        }

        return $couriers;
    }
}
