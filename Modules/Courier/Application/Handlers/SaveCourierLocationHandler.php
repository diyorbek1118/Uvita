<?php

declare(strict_types=1);

namespace Modules\Courier\Application\Handlers;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Courier\Application\Commands\SaveCourierLocationCommand;
use Modules\Courier\Infrastructure\Persistence\Models\CourierLocation;
use Modules\Courier\Infrastructure\Persistence\Models\CourierProfile;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;

final class SaveCourierLocationHandler
{
    public function handle(SaveCourierLocationCommand $command): CourierLocation
    {
        OrderModel::query()
            ->where('id', $command->orderId)
            ->where('courier_id', $command->courierId)
            ->where('status', 'delivering')
            ->first()
            ?? throw new ModelNotFoundException('Faol yetkazish topilmadi.');

        CourierProfile::updateOrCreate(
            ['courier_id' => $command->courierId],
            ['last_seen_at' => now()]
        );

        return CourierLocation::create([
            'courier_id' => $command->courierId,
            'order_id' => $command->orderId,
            'latitude' => $command->latitude,
            'longitude' => $command->longitude,
            'accuracy' => $command->accuracy,
            'recorded_at' => $command->recordedAt !== null
                ? CarbonImmutable::parse($command->recordedAt)
                : now(),
        ]);
    }
}
