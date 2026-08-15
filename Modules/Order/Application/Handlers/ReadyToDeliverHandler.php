<?php

declare(strict_types=1);

namespace Modules\Order\Application\Handlers;

use App\Jobs\SendSmsJob;
use App\Jobs\SendTelegramJob;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Modules\Courier\Application\Services\DeliveryConfirmationService;
use Modules\Order\Application\Commands\ReadyToDeliverCommand;
use Modules\Order\Domain\Exceptions\InsufficientStockException;
use Modules\Order\Domain\Repositories\OrderRepositoryInterface;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;
use Modules\Payment\Domain\Enums\PaymentProvider;
use Modules\Payment\Infrastructure\Persistence\Models\PaymentModel;
use Modules\Product\Infrastructure\Persistence\Models\Product;

final class ReadyToDeliverHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly DeliveryConfirmationService $confirmation,
    ) {}

    public function handle(ReadyToDeliverCommand $command): OrderModel
    {
        [$saved, $proof] = DB::transaction(function () use ($command): array {
            $orderModel = OrderModel::query()->lockForUpdate()->findOrFail($command->orderId);
            $order = $this->orders->findById($command->orderId)
                ?? throw new ModelNotFoundException('Buyurtma topilmadi.');

            // Status avval tekshiriladi: takroriy request stokni ikkinchi marta kamaytirmaydi.
            $order->markReadyToDeliver($command->courierNote);

            $payment = PaymentModel::query()
                ->where('order_id', $command->orderId)
                ->latest('id')
                ->lockForUpdate()
                ->first();

            // Onlayn to'lovlarda stok payment webhookida kamayadi. COD uchun esa
            // manager mahsulotni real yig'ib, "tayyor" degan paytda kamaytiramiz.
            if ($payment?->provider === PaymentProvider::CASH) {
                $items = $orderModel->items()->orderBy('product_id')->get();
                $products = Product::query()
                    ->whereIn('id', $items->pluck('product_id'))
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                foreach ($items as $item) {
                    $product = $products->get($item->product_id);
                    $reservationMissing = $orderModel->stock_reserved_at !== null
                        && ($product === null || $product->reserved_stock < $item->quantity);
                    if ($product === null || $product->stock < $item->quantity || $reservationMissing) {
                        throw new InsufficientStockException(
                            "\"{$product?->name}\" mahsulotidan yetarli qoldiq qolmagan. Buyurtma tarkibini yangilang."
                        );
                    }
                }
                foreach ($items as $item) {
                    $product = $products->get($item->product_id);
                    $product->decrement('stock', $item->quantity);
                    if ($orderModel->stock_reserved_at !== null) {
                        $product->decrement('reserved_stock', $item->quantity);
                    }
                }

                // Qaysi buyurtma stokni band qilganini aniq saqlaymiz. Shu belgi
                // bekor qilishda stokni faqat bir marta xavfsiz qaytarish uchun kerak.
                $orderModel->update([
                    'stock_committed_at' => now(),
                    'stock_released_at' => null,
                ]);
            }

            $saved = $this->orders->save($order);
            $proof = $this->confirmation->ensureForOrder($saved->id);

            return [$saved, $proof];
        });
        $pin = $this->confirmation->reveal($proof);

        dispatch(new SendSmsJob(
            $saved->phone,
            "Buyurtma #{$saved->id} tayyor. Yetkazish tasdiqlash kodi: {$pin}"
        ));

        dispatch(new SendTelegramJob(
            role: 'admin',
            message: "📦 <b>Buyurtma #{$saved->id} tayyor</b>\n\nMahsulotlar yig'ildi, kuryerga topshirishga tayyor.\n📞 {$saved->phone}"
        ));

        return OrderModel::with(['items.product'])->findOrFail($saved->id);
    }
}
