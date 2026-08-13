<?php

declare(strict_types=1);

namespace Modules\Courier\Application\Handlers;

use App\Shared\Exceptions\DomainException;
use Modules\Courier\Application\Commands\SetCourierAvailabilityCommand;
use Modules\Courier\Infrastructure\Persistence\Models\CourierProfile;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;

final class SetCourierAvailabilityHandler
{
    public function handle(SetCourierAvailabilityCommand $command): CourierProfile
    {
        if (! $command->isOnline && OrderModel::query()
            ->where('courier_id', $command->courierId)
            ->where('status', 'delivering')
            ->exists()) {
            throw new DomainException('Faol yetkazish tugamaguncha smenani yopib bo‘lmaydi.');
        }

        $profile = CourierProfile::firstOrCreate(['courier_id' => $command->courierId]);
        $profile->update([
            'is_online' => $command->isOnline,
            'shift_started_at' => $command->isOnline ? now() : $profile->shift_started_at,
            'shift_ended_at' => $command->isOnline ? null : now(),
            'last_seen_at' => now(),
        ]);

        return $profile->fresh();
    }
}
