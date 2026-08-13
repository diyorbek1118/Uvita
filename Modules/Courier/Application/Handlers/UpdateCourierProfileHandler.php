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

            CourierProfile::updateOrCreate(
                ['courier_id' => $courier->id],
                [
                    'phone' => $command->phone,
                    'vehicle_type' => $command->vehicleType,
                    'vehicle_number' => $command->vehicleNumber,
                    'photo' => $command->photo,
                ]
            );

            return $courier->fresh()->load('courierProfile');
        });
    }
}
