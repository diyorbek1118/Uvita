<?php

declare(strict_types=1);

namespace Modules\Courier\Application\Handlers;

use Modules\Admin\Domain\Enums\StaffRole;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Modules\Courier\Application\Queries\GetCourierProfileQuery;

final class GetCourierProfileHandler
{
    public function handle(GetCourierProfileQuery $query): Staff
    {
        $courier = Staff::find($query->courierId);

        if ($courier === null) {
            abort(404, 'Kuryer topilmadi.');
        }

        if ($courier->role !== StaffRole::COURIER) {
            abort(403, 'Bu foydalanuvchi kuryer emas.');
        }

        return $courier;
    }
}
