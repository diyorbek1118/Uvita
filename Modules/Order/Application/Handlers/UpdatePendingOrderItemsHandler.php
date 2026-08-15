<?php

declare(strict_types=1);

namespace Modules\Order\Application\Handlers;

use App\Shared\Exceptions\DomainException;
use App\Shared\Services\Fee\OrderFeeCalculator;
use App\Shared\Services\Settings\SettingService;
use Illuminate\Support\Facades\DB;
use Modules\Order\Application\Commands\UpdatePendingOrderItemsCommand;
use Modules\Order\Domain\Enums\OrderStatus;
use Modules\Order\Domain\Exceptions\InsufficientStockException;
use Modules\Order\Domain\Exceptions\MinimumOrderAmountException;
use Modules\Order\Infrastructure\Persistence\Models\OrderItemModel;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;
use Modules\Payment\Domain\Enums\PaymentProvider;
use Modules\Payment\Domain\Enums\PaymentStatus;
use Modules\Payment\Infrastructure\Persistence\Models\PaymentModel;
use Modules\Product\Domain\Enums\ProductStatusEnum;
use Modules\Product\Infrastructure\Persistence\Models\Product;

final class UpdatePendingOrderItemsHandler
{
    public function __construct(
        private readonly SettingService $settings,
        private readonly OrderFeeCalculator $fees,
    ) {}

    public function handle(UpdatePendingOrderItemsCommand $command): OrderModel
    {
        return DB::transaction(function () use ($command): OrderModel {
            $order = OrderModel::query()->lockForUpdate()->findOrFail($command->orderId);
            $payment = PaymentModel::query()
                ->where('order_id', $order->id)
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (
                $order->status !== OrderStatus::PENDING
                || $payment?->provider !== PaymentProvider::CASH
                || $payment->status !== PaymentStatus::PENDING
            ) {
                throw new DomainException('Faqat tasdiqlanmagan naqd buyurtma tarkibini o‘zgartirish mumkin.');
            }

            $requested = collect($command->items)->keyBy('product_id');
            $oldItems = $order->items()->get()->keyBy('product_id');
            $productIds = $requested->keys()->merge($oldItems->keys())->unique();
            $products = Product::withTrashed()
                ->whereIn('id', $productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $oldPrices = $oldItems->pluck('price', 'product_id');
            $prepared = [];
            $total = 0;
            $hasReservation = $order->stock_reserved_at !== null;

            foreach ($requested as $productId => $item) {
                $product = $products->get((int) $productId);
                if ($product === null || $product->status !== ProductStatusEnum::Active) {
                    throw new DomainException('Tanlangan mahsulot hozir sotuvda emas.');
                }
                if ($order->seller_profile_id !== null && $product->seller_profile_id !== $order->seller_profile_id) {
                    throw new DomainException('Bu buyurtmaga boshqa seller mahsulotini qo‘shib bo‘lmaydi. Alohida buyurtma yaratilishi kerak.');
                }

                $quantity = (int) $item['quantity'];
                if ($quantity < $product->minimum_order_quantity) {
                    throw new DomainException(
                        "\"{$product->name}\" mahsulotidan kamida {$product->minimum_order_quantity} {$product->unit} buyurtma qilish kerak."
                    );
                }
                $ownReserved = $hasReservation ? (int) ($oldItems->get($product->id)?->quantity ?? 0) : 0;
                $availableForOrder = max(0, $product->stock - $product->reserved_stock + $ownReserved);
                if ($quantity > $availableForOrder) {
                    throw new InsufficientStockException(
                        "\"{$product->name}\" mahsulotidan faqat {$availableForOrder} {$product->unit} mavjud."
                    );
                }

                $price = (int) ($oldPrices->get($product->id) ?? $product->price);
                $prepared[] = ['product_id' => $product->id, 'quantity' => $quantity, 'price' => $price];
                $total += $price * $quantity;
            }

            $minimum = $this->settings->minOrderAmount();
            $otherOrdersTotal = $order->checkout_group_id !== null
                ? (int) OrderModel::query()
                    ->where('checkout_group_id', $order->checkout_group_id)
                    ->where('id', '!=', $order->id)
                    ->where('status', '!=', OrderStatus::CANCELLED->value)
                    ->sum('total_price')
                : 0;
            if (($total + $otherOrdersTotal) < $minimum) {
                throw new MinimumOrderAmountException(
                    'Yangilangan umumiy buyurtmalar kamida '.number_format($minimum, 0, '.', ' ').' so‘m bo‘lishi kerak.'
                );
            }

            if ($hasReservation) {
                foreach ($oldItems as $oldItem) {
                    $product = $products->get($oldItem->product_id);
                    if ($product !== null) {
                        $product->update([
                            'reserved_stock' => max(0, $product->reserved_stock - $oldItem->quantity),
                        ]);
                    }
                }
            }
            foreach ($prepared as $item) {
                $products->get($item['product_id'])->increment('reserved_stock', $item['quantity']);
            }

            $financials = $this->fees->calculate($total);
            $order->items()->delete();
            foreach ($prepared as $item) {
                OrderItemModel::create(['order_id' => $order->id, ...$item]);
            }

            $order->update([
                'total_price' => $total,
                'service_fee' => $financials->platformFeeGross,
                'courier_fee' => $financials->courierFee,
                'grand_total' => $financials->customerTotal,
                'stock_reserved_at' => $order->stock_reserved_at ?? now(),
            ]);
            $payment->update(['amount' => $financials->customerTotal * 100]);

            return $order->fresh(['items.product', 'user', 'courier', 'latestPayment']);
        });
    }
}
