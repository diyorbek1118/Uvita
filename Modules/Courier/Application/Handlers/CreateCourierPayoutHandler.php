<?php

declare(strict_types=1);

namespace Modules\Courier\Application\Handlers;

use App\Shared\Exceptions\DomainException;
use Illuminate\Support\Facades\DB;
use Modules\Courier\Application\Commands\CreateCourierPayoutCommand;
use Modules\Courier\Domain\Enums\CourierPayoutStatus;
use Modules\Courier\Infrastructure\Persistence\Models\CourierPayout;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;

final class CreateCourierPayoutHandler
{
    public function handle(CreateCourierPayoutCommand $command): CourierPayout
    {
        return DB::transaction(function () use ($command): CourierPayout {
            $exists = CourierPayout::query()
                ->where('courier_id', $command->courierId)
                ->whereDate('period_start', $command->periodStart)
                ->whereDate('period_end', $command->periodEnd)
                ->lockForUpdate()
                ->exists();
            if ($exists) {
                throw new DomainException('Bu davr uchun hisob-kitob allaqachon yaratilgan.');
            }

            $amount = (int) OrderModel::query()
                ->where('courier_id', $command->courierId)
                ->where('status', 'delivered')
                ->whereBetween('delivered_at', [
                    $command->periodStart.' 00:00:00',
                    $command->periodEnd.' 23:59:59',
                ])
                ->sum('courier_fee');
            if ($amount <= 0) {
                throw new DomainException('Tanlangan davrda to‘lanadigan yetkazish haqi yo‘q.');
            }

            return CourierPayout::create([
                'courier_id' => $command->courierId,
                'period_start' => $command->periodStart,
                'period_end' => $command->periodEnd,
                'amount' => $amount,
                'status' => CourierPayoutStatus::PENDING,
                'note' => $command->note,
                'created_by' => $command->createdBy,
            ]);
        });
    }
}
