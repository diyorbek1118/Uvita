<?php

declare(strict_types=1);

namespace Modules\Order\Application\Handlers;

use App\Jobs\ClearCartJob;
use App\Jobs\SendSmsJob;
use App\Jobs\SendTelegramJob;
use App\Shared\Exceptions\DomainException;
use App\Shared\Services\Fee\OrderFeeCalculator;
use App\Shared\Services\Settings\SettingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Order\Application\Commands\CreateOrderCommand;
use Modules\Order\Application\DTOs\CreatedOrdersResult;
use Modules\Order\Domain\Entities\Order;
use Modules\Order\Domain\Entities\OrderItem;
use Modules\Order\Domain\Enums\OrderStatus;
use Modules\Order\Domain\Exceptions\InsufficientStockException;
use Modules\Order\Domain\Exceptions\MinimumOrderAmountException;
use Modules\Order\Domain\Repositories\OrderRepositoryInterface;
use Modules\Order\Domain\ValueObjects\DeliveryAddress;
use Modules\Order\Domain\ValueObjects\DeliveryTime;
use Modules\Order\Domain\ValueObjects\Money;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;
use Modules\Payment\Application\Commands\CreatePaymentCommand;
use Modules\Payment\Application\Handlers\CreatePaymentHandler;
use Modules\Payment\Domain\Enums\PaymentProvider;
use Modules\Product\Domain\Enums\ProductStatusEnum;
use Modules\Product\Infrastructure\Persistence\Models\Product as ProductModel;

final class CreateOrderHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly CreatePaymentHandler $createPaymentHandler,
        private readonly SettingService $settingService,
        private readonly OrderFeeCalculator $feeCalculator,
    ) {}

    public function handle(CreateOrderCommand $command): CreatedOrdersResult
    {
        $dto = $command->dto;

        $createdOrders = DB::transaction(function () use ($dto) {
            $requested = collect($dto->items)->keyBy('product_id');
            $products = ProductModel::query()
                ->whereIn('id', $requested->keys())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $prepared = collect();
            $checkoutTotal = 0;
            $isCash = $dto->paymentMethod === PaymentProvider::CASH->value;

            foreach ($dto->items as $item) {
                $product = $products->get((int) $item['product_id']);
                if ($product === null) {
                    throw new DomainException('Tanlangan mahsulot topilmadi.');
                }
                if ($product->status !== ProductStatusEnum::Active) {
                    throw new DomainException("\"{$product->name}\" mahsuloti hozir sotuvda emas.");
                }

                $quantity = (int) $item['quantity'];
                if ($quantity > $product->available_stock) {
                    throw new InsufficientStockException(
                        "\"{$product->name}\" mahsulotidan faqat {$product->available_stock} {$product->unit} mavjud."
                    );
                }
                if ($quantity < $product->minimum_order_quantity) {
                    throw new DomainException(
                        "\"{$product->name}\" mahsulotidan kamida {$product->minimum_order_quantity} {$product->unit} buyurtma qilishingiz kerak."
                    );
                }

                $subtotal = $product->price * $quantity;
                $checkoutTotal += $subtotal;
                $prepared->push([
                    'product' => $product,
                    'quantity' => $quantity,
                    'subtotal' => $subtotal,
                    'fulfillment_key' => $this->fulfillmentKey($product),
                ]);
            }

            $minOrder = $this->settingService->minOrderAmount();
            if ($checkoutTotal < $minOrder) {
                throw new MinimumOrderAmountException(
                    'Minimal buyurtma summasi '.number_format($minOrder, 0, '.', ' ')." so'm. "
                    .'Savatchangiz: '.number_format($checkoutTotal, 0, '.', ' ')." so'm."
                );
            }

            $groups = $prepared->groupBy('fulfillment_key');
            $checkoutGroupId = (string) Str::uuid();
            if (! $isCash && $groups->count() > 1) {
                throw new DomainException('Bir nechta sellerdan xarid hozircha faqat naqd to‘lovda mavjud.');
            }

            if ($isCash) {
                foreach ($prepared as $entry) {
                    $entry['product']->increment('reserved_stock', $entry['quantity']);
                }
            }

            return $groups->map(function ($entries) use ($dto, $isCash, $checkoutGroupId): OrderModel {
                $groupTotal = (int) $entries->sum('subtotal');
                $financials = $this->feeCalculator->calculate($groupTotal);
                $orderItems = $entries->map(fn (array $entry): OrderItem => new OrderItem(
                    id: null,
                    orderId: null,
                    productId: $entry['product']->id,
                    quantity: $entry['quantity'],
                    price: new Money($entry['product']->price),
                ))->all();

                $order = new Order(
                    id: null,
                    userId: $dto->userId,
                    status: OrderStatus::PENDING,
                    address: DeliveryAddress::fromArray($dto->address),
                    deliveryLatitude: $dto->deliveryLatitude,
                    deliveryLongitude: $dto->deliveryLongitude,
                    geoLevel: $dto->geoLevel,
                    phone: $dto->phone,
                    phoneSecondary: $dto->phoneSecondary,
                    deliveryTime: new DeliveryTime($dto->deliveryTime),
                    serviceFee: new Money($financials->platformFeeGross),
                    courierFee: new Money($financials->courierFee),
                    totalPrice: new Money($groupTotal),
                    grandTotal: new Money($financials->customerTotal),
                    items: $orderItems,
                    courierNote: $dto->courierNote,
                );

                $saved = $this->orders->save($order);
                $sellerProfileIds = $entries->map(fn (array $entry) => $entry['product']->seller_profile_id)
                    ->filter()
                    ->unique()
                    ->values();
                $sellerProfileId = $sellerProfileIds->count() === 1 ? $sellerProfileIds->first() : null;
                OrderModel::query()->whereKey($saved->id)->update([
                    'seller_profile_id' => $sellerProfileId,
                    'checkout_group_id' => $checkoutGroupId,
                    'stock_reserved_at' => $isCash ? now() : null,
                    'delivery_scope' => $dto->deliveryScope,
                ]);

                $paymentResult = $this->createPaymentHandler->handle(new CreatePaymentCommand(
                    orderId: $saved->id,
                    provider: $dto->paymentMethod,
                    userId: $dto->userId,
                ));

                $model = OrderModel::with(['items.product', 'latestPayment'])->findOrFail($saved->id);
                $model->setAttribute('payment_url', $paymentResult['payment_url'] ?? null);

                return $model;
            })->values();
        });

        dispatch(new ClearCartJob($dto->userId));
        $ids = $createdOrders->pluck('id')->implode(', #');
        dispatch(new SendSmsJob($dto->phone, "Buyurtmalar #{$ids} yaratildi."));
        foreach ($createdOrders as $order) {
            dispatch(new SendTelegramJob(
                role: 'manager',
                message: "🛒 <b>Yangi buyurtma #{$order->id}</b>\n\n📞 {$dto->phone}\n💰 {$order->grand_total} so'm\n🕐 {$dto->deliveryTime}"
            ));
        }

        return new CreatedOrdersResult($createdOrders);
    }

    private function fulfillmentKey(ProductModel $product): string
    {
        return match (true) {
            $product->seller_profile_id !== null => 'shop:'.$product->seller_profile_id,
            $product->seller_id !== null => 'seller:'.$product->seller_id,
            $product->manager_id !== null => 'manager:'.$product->manager_id,
            default => 'legacy',
        };
    }
}
