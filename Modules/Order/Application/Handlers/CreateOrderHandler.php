<?php

declare(strict_types=1);

namespace Modules\Order\Application\Handlers;

use App\Jobs\ClearCartJob;
use App\Jobs\SendSmsJob;
use App\Jobs\SendTelegramJob;
use Illuminate\Support\Facades\DB;
use Modules\Order\Application\Commands\CreateOrderCommand;
use Modules\Order\Domain\Entities\Order;
use Modules\Order\Domain\Entities\OrderItem;
use Modules\Order\Domain\Enums\OrderStatus;
use Modules\Order\Domain\Exceptions\InsufficientStockException;
use Modules\Order\Domain\Repositories\OrderRepositoryInterface;
use Modules\Order\Domain\ValueObjects\DeliveryAddress;
use Modules\Order\Domain\ValueObjects\DeliveryTime;
use Modules\Order\Domain\ValueObjects\Money;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;
use Modules\Payment\Application\Commands\CreatePaymentCommand;
use Modules\Payment\Application\Handlers\CreatePaymentHandler;
use Modules\Product\Infrastructure\Persistence\Models\Product as ProductModel;

final class CreateOrderHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly CreatePaymentHandler     $createPaymentHandler,
    ) {}

    public function handle(CreateOrderCommand $command): OrderModel
    {
        $dto = $command->dto;

        $savedOrder = DB::transaction(function () use ($dto) {
            $totalAmount = 0;
            $orderItems  = [];

            foreach ($dto->items as $item) {
                $product = ProductModel::lockForUpdate()->findOrFail($item['product_id']);

                if ($product->stock < $item['quantity']) {
                    throw new InsufficientStockException(
                        "\"{$product->name}\" mahsulotidan yetarli stok yo'q."
                    );
                }

                $totalAmount += $product->price * $item['quantity'];

                $orderItems[] = new OrderItem(
                    id:        null,
                    orderId:   null,
                    productId: $product->id,
                    quantity:  $item['quantity'],
                    price:     new Money($product->price),
                );
            }

            $deliveryPrice = (int) config('cart.delivery_price', 15000);

            $order = new Order(
                id:             null,
                userId:         $dto->userId,
                status:         OrderStatus::PENDING,
                address:        DeliveryAddress::fromArray($dto->address),
                phone:          $dto->phone,
                phoneSecondary: $dto->phoneSecondary,
                deliveryTime:   new DeliveryTime($dto->deliveryTime),
                deliveryPrice:  new Money($deliveryPrice),
                totalPrice:     new Money($totalAmount),
                grandTotal:     new Money($totalAmount + $deliveryPrice),
                items:          $orderItems,
                courierNote:    $dto->courierNote,
            );

            return $this->orders->save($order);
        });

        $paymentResult = $this->createPaymentHandler->handle(new CreatePaymentCommand(
            orderId:  $savedOrder->id,
            amount:   0,
            provider: $dto->paymentMethod,
        ));

        dispatch(new ClearCartJob($dto->userId));
        dispatch(new SendSmsJob($dto->phone, "Buyurtma #{$savedOrder->id} yaratildi."));
        dispatch(new SendTelegramJob(
            chatId:  (string) config('telegram.manager_chat_id', ''),
            message: "🛒 <b>Yangi buyurtma #{$savedOrder->id}</b>\n\n📞 {$dto->phone}\n💰 {$savedOrder->grand_total} so'm\n🕐 {$dto->deliveryTime}"
        ));

        $orderModel = OrderModel::with(['items.product'])->findOrFail($savedOrder->id);
        $orderModel->setAttribute('payment_url', $paymentResult['payment_url'] ?? null);

        return $orderModel;
    }
}
