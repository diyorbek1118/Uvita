<?php

declare(strict_types=1);

namespace Modules\Order\Application\Handlers;

use App\Jobs\SendSmsJob;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Modules\Order\Application\Commands\CancelOrderCommand;
use Modules\Order\Domain\Repositories\OrderRepositoryInterface;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;
use Modules\Payment\Domain\Enums\PaymentStatus;
use Modules\Payment\Infrastructure\Persistence\Models\PaymentModel;
use Modules\Product\Infrastructure\Persistence\Models\Product;

final class CancelOrderHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
    ) {}

    public function handle(CancelOrderCommand $command): OrderModel
    {
        [$saved, $phone] = DB::transaction(function () use ($command): array {
            $orderModel = OrderModel::query()->lockForUpdate()->findOrFail($command->orderId);

            $order = $this->orders->findById($command->orderId)
                ?? throw new ModelNotFoundException('Buyurtma topilmadi.');

            // Faqat o'z buyurtmasini bekor qila oladi
            if ($command->userId !== null && $order->userId !== $command->userId) {
                abort(403, "Ruxsat yo'q.");
            }

            $order->cancel();
            $saved = $this->orders->save($order);

            if ($orderModel->stock_reserved_at !== null && $orderModel->stock_committed_at === null) {
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
                        $product->update([
                            'reserved_stock' => max(0, $product->reserved_stock - $item->quantity),
                        ]);
                    }
                }
                $orderModel->update(['stock_released_at' => now()]);
            }

            PaymentModel::query()
                ->where('order_id', $command->orderId)
                ->where('status', PaymentStatus::PENDING->value)
                ->lockForUpdate()
                ->update(['status' => PaymentStatus::CANCELLED->value]);

            return [$saved, $order->phone];
        });

        dispatch(new SendSmsJob($phone, "Buyurtma #{$saved->id} bekor qilindi."));

        return OrderModel::with(['items.product', 'latestPayment'])->findOrFail($saved->id);
    }
}
