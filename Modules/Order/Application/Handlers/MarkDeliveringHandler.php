<?php

declare(strict_types=1);

namespace Modules\Order\Application\Handlers;

use App\Jobs\SendSmsJob;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Modules\Courier\Application\Services\DeliveryConfirmationService;
use Modules\Courier\Domain\Enums\DeliveryAssignmentStatus;
use Modules\Courier\Infrastructure\Persistence\Models\DeliveryAssignment;
use Modules\Order\Application\Commands\MarkDeliveringCommand;
use Modules\Order\Domain\Repositories\OrderRepositoryInterface;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;

final class MarkDeliveringHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly DeliveryConfirmationService $confirmation,
    ) {}

    public function handle(MarkDeliveringCommand $command): OrderModel
    {
        $saved = DB::transaction(function () use ($command) {
            OrderModel::query()->lockForUpdate()->findOrFail($command->orderId);
            $order = $this->orders->findById($command->orderId)
                ?? throw new ModelNotFoundException('Buyurtma topilmadi.');

            if ($order->courierId !== $command->courierId) {
                throw new ModelNotFoundException('Buyurtma topilmadi.');
            }

            $order->markDelivering();
            $saved = $this->orders->save($order);

            $assignment = DeliveryAssignment::query()
                ->where('order_id', $command->orderId)
                ->where('courier_id', $command->courierId)
                ->where('status', DeliveryAssignmentStatus::ASSIGNED->value)
                ->latest('id')
                ->first();

            if ($assignment !== null) {
                $assignment->update([
                    'status' => DeliveryAssignmentStatus::ACCEPTED,
                    'responded_at' => now(),
                ]);
            } else {
                DeliveryAssignment::create([
                    'order_id' => $command->orderId,
                    'courier_id' => $command->courierId,
                    'status' => DeliveryAssignmentStatus::ACCEPTED,
                    'assigned_at' => now(),
                    'responded_at' => now(),
                ]);
            }

            $this->confirmation->ensureForOrder($saved->id);

            return $saved;
        });

        $savedModel = OrderModel::findOrFail($saved->id);
        dispatch(new SendSmsJob($savedModel->phone, "Kuryer yo'lda, tez orada yetkaziladi."));

        return OrderModel::with(['items.product', 'deliveryAssignments'])->findOrFail($saved->id);
    }
}
