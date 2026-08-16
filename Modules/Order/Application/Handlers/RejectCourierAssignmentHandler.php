<?php

declare(strict_types=1);

namespace Modules\Order\Application\Handlers;

use App\Jobs\SendTelegramJob;
use App\Shared\Exceptions\DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Modules\Courier\Domain\Enums\DeliveryAssignmentStatus;
use Modules\Courier\Infrastructure\Persistence\Models\CourierTripOrder;
use Modules\Courier\Infrastructure\Persistence\Models\DeliveryAssignment;
use Modules\Order\Application\Commands\RejectCourierAssignmentCommand;
use Modules\Order\Domain\Repositories\OrderRepositoryInterface;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;

final class RejectCourierAssignmentHandler
{
    public function __construct(private readonly OrderRepositoryInterface $orders) {}

    public function handle(RejectCourierAssignmentCommand $command): OrderModel
    {
        $saved = DB::transaction(function () use ($command) {
            OrderModel::query()->lockForUpdate()->findOrFail($command->orderId);
            $order = $this->orders->findById($command->orderId)
                ?? throw new ModelNotFoundException('Buyurtma topilmadi.');
            if (CourierTripOrder::query()->where('order_id', $command->orderId)->exists()) {
                throw new DomainException('Reysdagi bitta zakazdan voz kechib bo‘lmaydi. Yuk olinmagan bo‘lsa reysni to‘liq bekor qiling.');
            }

            $assignment = DeliveryAssignment::query()
                ->where('order_id', $command->orderId)
                ->where('courier_id', $command->courierId)
                ->whereIn('status', [
                    DeliveryAssignmentStatus::ASSIGNED->value,
                    DeliveryAssignmentStatus::ACCEPTED->value,
                ])
                ->latest('id')
                ->first();

            if ($assignment === null) {
                throw new ModelNotFoundException('Faol tayinlov topilmadi.');
            }
            if (
                $assignment->status === DeliveryAssignmentStatus::ACCEPTED
                && $assignment->assigned_at->lt(now()->subHours(5))
            ) {
                throw new DomainException('Qabul qilingan buyurtmani faqat dastlabki 5 soat ichida bekor qilish mumkin.');
            }

            $order->unassignCourier($command->courierId);
            $saved = $this->orders->save($order);
            $assignment->update([
                'status' => DeliveryAssignmentStatus::REJECTED,
                'rejection_reason' => $command->reason,
                'responded_at' => now(),
            ]);

            return $saved;
        });

        dispatch(new SendTelegramJob(
            role: 'admin',
            message: "⚠️ <b>Buyurtma #{$saved->id} kuryer tomonidan rad etildi</b>\n\nSabab: {$command->reason}"
        ));

        return OrderModel::with(['items.product', 'deliveryAssignments'])->findOrFail($saved->id);
    }
}
