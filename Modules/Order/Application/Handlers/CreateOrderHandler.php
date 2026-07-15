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
use Modules\Order\Domain\Exceptions\MinimumOrderAmountException;
use App\Shared\Services\Fee\OrderFeeCalculator;
use App\Shared\Services\Settings\SettingService;
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
        private readonly SettingService           $settingService,
        private readonly OrderFeeCalculator       $feeCalculator,
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

            // Minimal buyurtma tekshiruvi (mahsulotlar summasi bo'yicha)
            $minOrder = $this->settingService->minOrderAmount();
            if ($totalAmount < $minOrder) {
                throw new MinimumOrderAmountException(
                    "Minimal buyurtma summasi " . number_format($minOrder, 0, '.', ' ') . " so'm. "
                    . "Savatchangiz: " . number_format($totalAmount, 0, '.', ' ') . " so'm."
                );
            }

            // Narx breakdown: mijoz mahsulot + 15% xizmat haqi to'laydi.
            // Kuryer haqi platformadan (ichki) — mijozga ko'rinmaydi.
            $financials = $this->feeCalculator->calculate($totalAmount);

            $order = new Order(
                id:             null,
                userId:         $dto->userId,
                status:         OrderStatus::PENDING,
                address:        DeliveryAddress::fromArray($dto->address),
                phone:          $dto->phone,
                phoneSecondary: $dto->phoneSecondary,
                deliveryTime:   new DeliveryTime($dto->deliveryTime),
                serviceFee:     new Money($financials->platformFeeGross),
                courierFee:     new Money($financials->courierFee),
                totalPrice:     new Money($totalAmount),
                grandTotal:     new Money($financials->customerTotal),
                items:          $orderItems,
                courierNote:    $dto->courierNote,
            );

            return $this->orders->save($order);
        });

        $paymentResult = $this->createPaymentHandler->handle(new CreatePaymentCommand(
            orderId:  $savedOrder->id,
            provider: $dto->paymentMethod,
        ));

        dispatch(new ClearCartJob($dto->userId));
        dispatch(new SendSmsJob($dto->phone, "Buyurtma #{$savedOrder->id} yaratildi."));
        dispatch(new SendTelegramJob(
            role:    'manager',
            message: "🛒 <b>Yangi buyurtma #{$savedOrder->id}</b>\n\n📞 {$dto->phone}\n💰 {$savedOrder->grandTotal->amount} so'm\n🕐 {$dto->deliveryTime}"
        ));

        $orderModel = OrderModel::with(['items.product'])->findOrFail($savedOrder->id);
        $orderModel->setAttribute('payment_url', $paymentResult['payment_url'] ?? null);

        return $orderModel;
    }
}
