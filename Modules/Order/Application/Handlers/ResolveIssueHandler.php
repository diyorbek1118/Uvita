<?php

declare(strict_types=1);

namespace Modules\Order\Application\Handlers;

use App\Jobs\SendSmsJob;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Modules\Courier\Application\Contracts\CourierNotifierInterface;
use Modules\Courier\Domain\Enums\DeliveryAssignmentStatus;
use Modules\Courier\Infrastructure\Persistence\Models\DeliveryAssignment;
use Modules\Order\Application\Commands\DeliveryIssueResolveCommand;
use Modules\Order\Domain\Repositories\OrderRepositoryInterface;
use Modules\Order\Domain\ValueObjects\DeliveryTime;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;
use Modules\Payment\Domain\Enums\PaymentStatus;
use Modules\Payment\Infrastructure\Persistence\Models\PaymentModel;
use Modules\Product\Infrastructure\Persistence\Models\Product;

final class ResolveIssueHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly CourierNotifierInterface $notifier,
    ) {}

    public function handle(DeliveryIssueResolveCommand $command): OrderModel
    {
        [$saved, $phone, $previousCourierId] = DB::transaction(function () use ($command): array {
            $orderModel = OrderModel::query()->lockForUpdate()->findOrFail($command->orderId);

            $order = $this->orders->findById($command->orderId)
                ?? throw new ModelNotFoundException('Buyurtma topilmadi.');
            $previousCourierId = $order->courierId;

            $deliveryTime = $command->deliveryTime !== null
                ? new DeliveryTime($command->deliveryTime)
                : null;

            $order->resolveDeliveryIssue($command->action, $deliveryTime);
            $saved = $this->orders->save($order);

            DeliveryAssignment::query()
                ->where('order_id', $command->orderId)
                ->whereIn('status', [
                    DeliveryAssignmentStatus::ASSIGNED->value,
                    DeliveryAssignmentStatus::ACCEPTED->value,
                ])
                ->update([
                    'status' => DeliveryAssignmentStatus::CANCELLED->value,
                    'responded_at' => now(),
                ]);

            if ($command->action === 'cancel') {
                if ($orderModel->stock_committed_at !== null && $orderModel->stock_released_at === null) {
                    $items = $orderModel->items()->orderBy('product_id')->get();
                    $products = Product::withTrashed()
                        ->whereIn('id', $items->pluck('product_id'))
                        ->orderBy('id')
                        ->lockForUpdate()
                        ->get()
                        ->keyBy('id');

                    foreach ($items as $item) {
                        $product = $products->get($item->product_id);
                        if ($product !== null) {
                            $product->increment('stock', $item->quantity);
                        }
                    }

                    $orderModel->update(['stock_released_at' => now()]);
                }

                $payment = PaymentModel::query()
                    ->where('order_id', $command->orderId)
                    ->latest('id')
                    ->lockForUpdate()
                    ->first();

                if ($payment?->status === PaymentStatus::PAID) {
                    $payment->update([
                        'status' => PaymentStatus::REFUND_PENDING->value,
                        'refund_requested_at' => now(),
                    ]);
                } elseif ($payment?->status === PaymentStatus::PENDING) {
                    $payment->update(['status' => PaymentStatus::CANCELLED->value]);
                }
            }

            return [$saved, $order->phone, $previousCourierId];
        });

        $message = $command->action === 'reschedule'
            ? "Buyurtma #{$saved->id} qayta yetkazishga rejalashtirildi."
            : "Buyurtma #{$saved->id} bekor qilindi. Pulni qaytarish jarayoni boshlandi.";

        dispatch(new SendSmsJob($phone, $message));

        if ($previousCourierId !== null) {
            $this->notifier->notify(
                $previousCourierId,
                $command->action === 'reschedule' ? 'order_rescheduled' : 'order_cancelled',
                $command->action === 'reschedule' ? 'Buyurtma qayta rejalashtirildi' : 'Buyurtma bekor qilindi',
                "Buyurtma #{$saved->id} bo'yicha admin qarori yangilandi.",
                ['order_id' => $saved->id]
            );
        }

        return OrderModel::with(['items.product', 'latestPayment'])->findOrFail($saved->id);
    }
}
