<?php

declare(strict_types=1);

namespace Modules\Courier\Application\Handlers;

use Illuminate\Support\Facades\DB;
use Modules\Admin\Domain\Enums\StaffRole;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Modules\Courier\Application\Commands\UpdateCourierProfileCommand;
use Modules\Courier\Infrastructure\Persistence\Models\CourierProfile;

final class UpdateCourierProfileHandler
{
    public function handle(UpdateCourierProfileCommand $command): Staff
    {
        return DB::transaction(function () use ($command): Staff {
            $courier = Staff::query()
                ->where('role', StaffRole::COURIER->value)
                ->lockForUpdate()
                ->findOrFail($command->courierId);
            $courier->update(['name' => $command->name]);

            $profile = CourierProfile::firstOrNew(['courier_id' => $courier->id]);
            $profile->fill([
                'phone' => $command->phone,
                'vehicle_type' => $command->vehicleType,
                'vehicle_number' => $command->vehicleNumber,
                'photo' => $command->photo,
            ]);
            if ($command->vehicleCapacityKg !== null) {
                $profile->vehicle_capacity_kg = $command->vehicleCapacityKg;
            }
            if ($command->maxOrdersPerTrip !== null) {
                $profile->max_orders_per_trip = $command->maxOrdersPerTrip;
            }
            $profile->save();

            return $courier->fresh()->load('courierProfile');
        });
    }
}
