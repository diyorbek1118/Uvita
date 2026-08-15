<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Handlers;

use App\Jobs\SendSmsJob;
use App\Jobs\SendTelegramJob;
use App\Shared\Exceptions\DomainException;
use Illuminate\Support\Facades\DB;
use Modules\Order\Domain\Enums\OrderStatus;
use Modules\Order\Domain\Exceptions\InsufficientStockException;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;
use Modules\Payment\Application\Commands\MarkPaymentPaidCommand;
use Modules\Payment\Domain\Enums\PaymentStatus;
use Modules\Payment\Domain\Exceptions\DuplicateTransactionException;
use Modules\Payment\Domain\Exceptions\InvalidPaymentAmountException;
use Modules\Payment\Domain\Exceptions\PaymentNotFoundException;
use Modules\Payment\Infrastructure\Persistence\Models\PaymentModel;
use Modules\Product\Infrastructure\Persistence\Models\Product as ProductModel;

final class MarkPaymentPaidHandler
{
    public function handle(MarkPaymentPaidCommand $command): void
    {
        if ($command->transactionId === '') {
            throw new DomainException("To'lov tranzaksiya ID si bo'sh bo'lmasligi kerak.");
        }

        $processed = DB::transaction(function () use ($command): bool {
            $payment = null;
            $sameTransaction = PaymentModel::query()
                ->where('transaction_id', $command->transactionId)
                ->lockForUpdate()
                ->first();

            if ($sameTransaction !== null) {
                if (
                    $sameTransaction->order_id === $command->orderId
                    && $sameTransaction->provider->value === $command->provider
                    && $sameTransaction->status === PaymentStatus::PAID
                ) {
                    return false;
                }

                if (
                    $sameTransaction->order_id === $command->orderId
                    && $sameTransaction->provider->value === $command->provider
                    && $sameTransaction->status === PaymentStatus::PENDING
                ) {
                    $payment = $sameTransaction;
                } else {
                    throw new DuplicateTransactionException('Bu tranzaksiya avval ishlatilgan.');
                }
            }

            $payment ??= PaymentModel::query()
                ->where('order_id', $command->orderId)
                ->where('provider', $command->provider)
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($payment === null) {
                throw new PaymentNotFoundException("To'lov ma'lumoti topilmadi. Order #{$command->orderId}");
            }

            if ($payment->status === PaymentStatus::PAID) {
                if ($payment->transaction_id === $command->transactionId) {
                    return false;
                }

                throw new DuplicateTransactionException("Buyurtma to'lovi avval tasdiqlangan.");
            }

            if ($payment->status !== PaymentStatus::PENDING) {
                throw new PaymentNotFoundException("To'lov pending holatida emas.");
            }

            if ($payment->amount !== $command->amount) {
                throw new InvalidPaymentAmountException(
                    "To'lov summasi mos kelmadi. Kutilgan: {$payment->amount}, kelgan: {$command->amount}"
                );
            }

            $order = OrderModel::query()->lockForUpdate()->findOrFail($command->orderId);
            if ($order->status !== OrderStatus::PENDING) {
                throw new DomainException("Buyurtma to'lov kutish holatida emas.");
            }

            $items = $order->items()->orderBy('product_id')->get();
            $products = ProductModel::query()
                ->whereIn('id', $items->pluck('product_id'))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($items as $item) {
                $product = $products->get($item->product_id);
                if ($product === null || $product->available_stock < $item->quantity) {
                    throw new InsufficientStockException(
                        "\"{$product?->name}\" mahsulotidan yetarli stok qolmagan."
                    );
                }
            }

            foreach ($items as $item) {
                $products->get($item->product_id)->decrement('stock', $item->quantity);
            }

            $payment->update([
                'status' => PaymentStatus::PAID->value,
                'transaction_id' => $command->transactionId,
                'provider_transaction_id' => $command->transactionId,
            ]);

            $order->update([
                'status' => OrderStatus::PAID->value,
                'paid_at' => now(),
            ]);

            return true;
        });

        if (! $processed) {
            return;
        }

        $order = OrderModel::findOrFail($command->orderId);

        dispatch(new SendSmsJob(
            $order->phone,
            "To'lovingiz qabul qilindi. Buyurtma #{$command->orderId}"
        ));

        dispatch(new SendTelegramJob(
            role: 'manager',
            message: "✅ <b>Yangi to'langan buyurtma #{$command->orderId}</b>\n\n💰 ".number_format($order->grand_total, 0, '.', ' ')." so'm\n📞 {$order->phone}"
        ));
    }
}
