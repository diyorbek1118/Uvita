<?php

declare(strict_types=1);

namespace Modules\Order\Application\Handlers;

use App\Jobs\SendTelegramJob;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Modules\Courier\Application\Contracts\CourierNotifierInterface;
use Modules\Courier\Application\Services\DeliveryConfirmationService;
use Modules\Courier\Domain\Enums\DeliveryAssignmentStatus;
use Modules\Courier\Infrastructure\Persistence\Models\DeliveryAssignment;
use Modules\Order\Application\Commands\AssignCourierCommand;
use Modules\Order\Domain\Repositories\OrderRepositoryInterface;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;

final class AssignCourierHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly CourierNotifierInterface $notifier,
        private readonly DeliveryConfirmationService $confirmation,
    ) {}

    public function handle(AssignCourierCommand $command): OrderModel
    {
        $saved = DB::transaction(function () use ($command) {
            OrderModel::query()->lockForUpdate()->findOrFail($command->orderId);
            $order = $this->orders->findById($command->orderId)
                ?? throw new ModelNotFoundException('Buyurtma topilmadi.');

            DeliveryAssignment::query()
                ->where('order_id', $command->orderId)
                ->where('status', DeliveryAssignmentStatus::ASSIGNED->value)
                ->update([
                    'status' => DeliveryAssignmentStatus::CANCELLED->value,
                    'responded_at' => now(),
                ]);

            $order->assignCourier($command->courierId);
            $saved = $this->orders->save($order);

            DeliveryAssignment::create([
                'order_id' => $saved->id,
                'courier_id' => $command->courierId,
                'assigned_by' => $command->assignedById,
                'status' => DeliveryAssignmentStatus::ASSIGNED,
                'assigned_at' => now(),
            ]);
            $this->confirmation->ensureForOrder($saved->id);

            return $saved;
        });

        $this->notifier->notify(
            $command->courierId,
            'order_assigned',
            'Yangi buyurtma',
            "Sizga #{$saved->id} buyurtma tayinlandi.",
            ['order_id' => $saved->id]
        );

        dispatch(new SendTelegramJob(
            role: 'admin',
            message: "🚴 <b>Buyurtma #{$saved->id}</b>\n\nKuryer #{$command->courierId} tayinlandi."
        ));

        return OrderModel::with(['items.product', 'deliveryAssignments'])->findOrFail($saved->id);
    }
}
