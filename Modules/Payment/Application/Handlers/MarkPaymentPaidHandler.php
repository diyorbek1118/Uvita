<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Handlers;

use App\Jobs\SendSmsJob;
use App\Jobs\SendTelegramJob;
use Illuminate\Support\Facades\DB;
use Modules\Order\Domain\Enums\OrderStatus;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;
use Modules\Payment\Application\Commands\MarkPaymentPaidCommand;
use Modules\Payment\Domain\Exceptions\InvalidPaymentAmountException;
use Modules\Payment\Domain\Exceptions\PaymentNotFoundException;
use Modules\Payment\Domain\Enums\PaymentStatus;
use Modules\Payment\Infrastructure\Persistence\Models\PaymentModel;
use Modules\Product\Infrastructure\Persistence\Models\Product as ProductModel;

final class MarkPaymentPaidHandler
{
    public function handle(MarkPaymentPaidCommand $command): void
    {
        // 1. Idempotency: bu transaction_id allaqachon to'langan bo'lsa — skip
        $alreadyPaid = PaymentModel::where('transaction_id', $command->transactionId)
            ->where('status', PaymentStatus::PAID->value)
            ->exists();

        if ($alreadyPaid) {
            return;
        }

        // 2. Payment ni orderId bo'yicha topamiz
        $paymentModel = PaymentModel::where('order_id', $command->orderId)
            ->whereIn('status', [PaymentStatus::PENDING->value])
            ->latest()
            ->first();

        if ($paymentModel === null) {
            throw new PaymentNotFoundException("To'lov ma'lumoti topilmadi. Order #{$command->orderId}");
        }

        // 3. Amount mosligini tekshiramiz
        if ($paymentModel->amount !== $command->amount) {
            throw new InvalidPaymentAmountException(
                "To'lov summasi mos kelmadi. Kutilgan: {$paymentModel->amount}, kelgan: {$command->amount}"
            );
        }

        // 4. DB::transaction — atomik
        DB::transaction(function () use ($command, $paymentModel): void {
            // Payment → paid
            $paymentModel->update([
                'status'         => PaymentStatus::PAID->value,
                'transaction_id' => $command->transactionId,
            ]);

            // Order → paid
            $order = OrderModel::lockForUpdate()->findOrFail($command->orderId);
            $order->update(['status' => OrderStatus::PAID->value]);

            // Stock atomik kamaytirish
            foreach ($order->items()->get() as $item) {
                ProductModel::where('id', $item->product_id)
                    ->lockForUpdate()
                    ->firstOrFail()
                    ->decrement('stock', $item->quantity);
            }
        });

        // 5. SMS + Telegram (async)
        $order = OrderModel::findOrFail($command->orderId);

        dispatch(new SendSmsJob(
            $order->phone,
            "To'lovingiz qabul qilindi. Buyurtma #{$command->orderId}"
        ));

        dispatch(new SendTelegramJob(
            chatId:  (string) config('telegram.manager_chat_id', ''),
            message: "✅ <b>Yangi to'langan buyurtma #{$command->orderId}</b>\n\n💰 " . number_format($order->grand_total, 0, '.', ' ') . " so'm\n📞 {$order->phone}"
        ));
    }
}
