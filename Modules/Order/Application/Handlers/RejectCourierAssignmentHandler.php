<?php

declare(strict_types=1);

namespace Modules\Order\Application\Handlers;

use App\Jobs\SendTelegramJob;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Modules\Courier\Domain\Enums\DeliveryAssignmentStatus;
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

            $assignment = DeliveryAssignment::query()
                ->where('order_id', $command->orderId)
                ->where('courier_id', $command->courierId)
                ->where('status', DeliveryAssignmentStatus::ASSIGNED->value)
                ->latest('id')
                ->first();

            if ($assignment === null) {
                throw new ModelNotFoundException('Faol tayinlov topilmadi.');
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
