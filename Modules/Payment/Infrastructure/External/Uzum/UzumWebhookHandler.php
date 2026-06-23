<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\External\Uzum;

use Illuminate\Http\Request;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;
use Modules\Payment\Application\Commands\MarkPaymentPaidCommand;
use Modules\Payment\Application\Handlers\MarkPaymentPaidHandler;
use Modules\Payment\Domain\Enums\PaymentStatus;
use Modules\Payment\Domain\Exceptions\InvalidSignatureException;
use Modules\Payment\Infrastructure\Persistence\Models\PaymentModel;

final class UzumWebhookHandler
{
    public function __construct(
        private readonly MarkPaymentPaidHandler $markPaidHandler,
    ) {}

    public function handle(Request $request): array
    {
        $this->verifySignature($request);

        $method = (string) ($request->input('method') ?? $request->input('action') ?? 'GetInformation');

        return match ($method) {
            'GetInformation'     => $this->getInformation($request),
            'PerformTransaction' => $this->performTransaction($request),
            'CancelTransaction'  => $this->cancelTransaction($request),
            default              => ['status' => -1, 'errorMessage' => 'Unknown method'],
        };
    }

    // ─── Methods ─────────────────────────────────────────────────────────────

    private function getInformation(Request $request): array
    {
        $orderId = (int) ($request->input('orderId') ?? $request->input('merchant_trans_id') ?? 0);

        $order = OrderModel::find($orderId);

        if ($order === null) {
            return ['status' => -1, 'errorMessage' => 'Order not found'];
        }

        return [
            'serviceId' => config('payment.uzum.service_id'),
            'timestamp' => now()->getTimestampMs(),
            'status'    => 0,
            'data'      => [
                'amount'   => $order->grand_total * 100, // tiyinlarda
                'currency' => 'UZS',
                'orderId'  => $orderId,
            ],
        ];
    }

    private function performTransaction(Request $request): array
    {
        $orderId       = (int) ($request->input('orderId') ?? 0);
        $transactionId = (string) ($request->input('transactionId') ?? $request->input('paymentId') ?? '');

        $payment = PaymentModel::where('order_id', $orderId)
            ->where('status', PaymentStatus::PENDING->value)
            ->latest()
            ->first();

        if ($payment === null) {
            return ['status' => -1, 'errorMessage' => 'Payment not found'];
        }

        // Idempotency
        if ($payment->status === PaymentStatus::PAID) {
            return ['status' => 0, 'transactionId' => $payment->transaction_id];
        }

        $this->markPaidHandler->handle(new MarkPaymentPaidCommand(
            orderId:       $orderId,
            transactionId: $transactionId,
            amount:        $payment->amount,
            provider:      'uzum',
        ));

        $payment->update([
            'transaction_id'          => $transactionId,
            'provider_transaction_id' => $transactionId,
        ]);

        return ['status' => 0, 'transactionId' => $transactionId];
    }

    private function cancelTransaction(Request $request): array
    {
        $orderId = (int) ($request->input('orderId') ?? 0);

        $payment = PaymentModel::where('order_id', $orderId)->latest()->first();

        if ($payment === null) {
            return ['status' => -1, 'errorMessage' => 'Payment not found'];
        }

        $payment->update(['status' => PaymentStatus::CANCELLED->value]);

        return ['status' => 0];
    }

    // ─── Signature ───────────────────────────────────────────────────────────

    private function verifySignature(Request $request): void
    {
        $username = (string) config('payment.uzum.username', '');
        $password = (string) config('payment.uzum.password', '');

        // Test rejimda yoki credentials bo'sh bo'lsa skip
        if (config('payment.test_mode') || $username === '' || $password === '') {
            return;
        }

        $authHeader = $request->header('Authorization', '');
        $base64Part = str_replace('Basic ', '', $authHeader);
        $decoded    = base64_decode($base64Part, strict: true) ?: '';
        $parts      = explode(':', $decoded, 2);

        if (($parts[0] ?? '') !== $username || ($parts[1] ?? '') !== $password) {
            throw new InvalidSignatureException('Uzum authorization error');
        }
    }
}
