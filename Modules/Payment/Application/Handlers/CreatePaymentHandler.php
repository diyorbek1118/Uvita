<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Handlers;

use Modules\Order\Domain\Enums\OrderStatus;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;
use Modules\Payment\Application\Commands\CreatePaymentCommand;
use Modules\Payment\Domain\Enums\PaymentStatus;
use Modules\Payment\Infrastructure\Persistence\Models\PaymentModel;

final class CreatePaymentHandler
{
    /** @return array{payment_id: int, payment_url: string} */
    public function handle(CreatePaymentCommand $command): array
    {
        // 1. Order topamiz
        $order = OrderModel::findOrFail($command->orderId);

        // 2. Status pending bo'lishi kerak
        if ($order->status !== OrderStatus::PENDING) {
            abort(422, "Buyurtma to'lov kutish (pending) holatida emas.");
        }

        // 3. Summa — YAGONA MANBA: order. Tiyinlarda (grand_total so'mda saqlanadi).
        $amount = (int) ($order->grand_total * 100);

        // 4. Mavjud pending payment bormi?
        $existing = PaymentModel::where('order_id', $command->orderId)
            ->where('status', PaymentStatus::PENDING->value)
            ->latest()
            ->first();

        if ($existing !== null) {
            // Order summasi o'zgargan bo'lishi mumkin — payment ni sinxronlaymiz
            if ($existing->amount !== $amount) {
                $existing->update(['amount' => $amount]);
            }

            return [
                'payment_id'  => $existing->id,
                'payment_url' => $this->buildUrl($existing->provider->value, $command->orderId, $amount),
            ];
        }

        // 5. Yangi payment yaratamiz
        $payment = PaymentModel::create([
            'order_id' => $command->orderId,
            'provider' => $command->provider,
            'amount'   => $amount,
            'status'   => PaymentStatus::PENDING->value,
        ]);

        // 6. URL generatsiya
        $url = $this->buildUrl($command->provider, $command->orderId, $amount);

        return ['payment_id' => $payment->id, 'payment_url' => $url];
    }

    private function buildUrl(string $provider, int $orderId, int $amount): string
    {
        return match ($provider) {
            'payme' => $this->buildPaymeUrl($orderId, $amount),
            'click' => $this->buildClickUrl($orderId, $amount),
            'uzum'  => $this->buildUzumUrl($orderId, $amount),
            default => '',
        };
    }

    private function buildPaymeUrl(int $orderId, int $amount): string
    {
        $id     = (string) config('payment.payme.id', '');
        $base   = (string) config('payment.payme.checkout', 'https://checkout.test.paycom.uz');
        $params = "m={$id};ac.order_id={$orderId};a={$amount}";

        return rtrim($base, '/') . '/' . base64_encode($params);
    }

    private function buildClickUrl(int $orderId, int $amount): string
    {
        $base       = (string) config('payment.click.checkout', 'https://my.click.uz/services/pay');
        $serviceId  = (string) config('payment.click.service_id', '');
        $merchantId = (string) config('payment.click.merchant_id', '');
        $amountSom  = $amount / 100; // tiyins → so'm (Click so'm qabul qiladi)

        return "{$base}?service_id={$serviceId}&merchant_id={$merchantId}&amount={$amountSom}&transaction_param={$orderId}";
    }

    private function buildUzumUrl(int $orderId, int $amount): string
    {
        $base      = (string) config('payment.uzum.checkout', 'https://secure.apelsin.uz/open-services/checkout');
        $serviceId = (string) config('payment.uzum.service_id', '');

        return "{$base}?serviceId={$serviceId}&orderId={$orderId}&amount={$amount}";
    }
}
