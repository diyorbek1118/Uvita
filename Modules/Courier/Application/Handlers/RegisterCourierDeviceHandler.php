<?php

declare(strict_types=1);

namespace Modules\Courier\Application\Handlers;

use Modules\Courier\Application\Commands\RegisterCourierDeviceCommand;
use Modules\Courier\Infrastructure\Persistence\Models\CourierDevice;

final class RegisterCourierDeviceHandler
{
    public function handle(RegisterCourierDeviceCommand $command): CourierDevice
    {
        return CourierDevice::updateOrCreate(
            ['token' => $command->token],
            [
                'courier_id' => $command->courierId,
                'platform' => $command->platform,
                'device_name' => $command->deviceName,
                'is_active' => true,
                'last_used_at' => now(),
            ]
        );
    }
}
