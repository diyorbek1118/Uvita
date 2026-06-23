<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\External\Payme;

use Illuminate\Http\Request;
use Modules\Order\Domain\Enums\OrderStatus;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;
use Modules\Payment\Application\Commands\MarkPaymentPaidCommand;
use Modules\Payment\Application\Handlers\MarkPaymentPaidHandler;
use Modules\Payment\Domain\Enums\PaymentStatus;
use Modules\Payment\Domain\Exceptions\InvalidSignatureException;
use Modules\Payment\Infrastructure\Persistence\Models\PaymentModel;

final class PaymeWebhookHandler
{
    private const LOGIN = 'Paycom';

    public function __construct(
        private readonly MarkPaymentPaidHandler $markPaidHandler,
    ) {}

    public function handle(Request $request): array
    {
        $this->verifySignature($request);

        $method = $request->input('method', '');
        $params = $request->input('params', []);
        $id     = $request->input('id');

        return match ($method) {
            'CheckPerformTransaction' => $this->checkPerformTransaction($params, $id),
            'CreateTransaction'       => $this->createTransaction($params, $id),
            'PerformTransaction'      => $this->performTransaction($params, $id),
            'CancelTransaction'       => $this->cancelTransaction($params, $id),
            'CheckTransaction'        => $this->checkTransaction($params, $id),
            'GetStatement'            => $this->getStatement($params, $id),
            default                   => $this->error(-32601, 'Method not found', $id),
        };
    }

    // ─── Signature ───────────────────────────────────────────────────────────

    private function verifySignature(Request $request): void
    {
        $authHeader = $request->header('Authorization', '');
        $base64Part = str_replace('Basic ', '', $authHeader);
        $decoded    = base64_decode($base64Part, strict: true) ?: '';
        $parts      = explode(':', $decoded, 2);
        $login      = $parts[0] ?? '';
        $password   = $parts[1] ?? '';

        $expectedKey = config('payment.test_mode')
            ? (string) config('payment.payme.test_key', '')
            : (string) config('payment.payme.key', '');

        if ($login !== self::LOGIN || ($expectedKey !== '' && $password !== $expectedKey)) {
            throw new InvalidSignatureException('Authorization error');
        }
    }

    // ─── Methods ─────────────────────────────────────────────────────────────

    private function checkPerformTransaction(array $params, mixed $id): array
    {
        $orderId = (int) ($params['account']['order_id'] ?? 0);
        $amount  = (int) ($params['amount'] ?? 0);

        $order = OrderModel::find($orderId);

        if ($order === null || $order->status !== OrderStatus::PENDING) {
            return $this->error(-31050, 'Order not found or not in pending state', $id);
        }

        $expected = $order->grand_total * 100;
        if ($amount !== $expected) {
            return $this->error(-31001, "Amount mismatch. Expected: {$expected}", $id);
        }

        return $this->result(['allow' => true], $id);
    }

    private function createTransaction(array $params, mixed $id): array
    {
        $transactionId = (string) ($params['id'] ?? '');
        $orderId       = (int) ($params['account']['order_id'] ?? 0);
        $amount        = (int) ($params['amount'] ?? 0);

        // Idempotency: bu transaction_id allaqachon bormi?
        $existing = PaymentModel::where('transaction_id', $transactionId)->first();
        if ($existing !== null) {
            return $this->result([
                'create_time' => $existing->created_at->getTimestampMs(),
                'transaction' => (string) $existing->id,
                'state'       => 1,
            ], $id);
        }

        $order = OrderModel::find($orderId);
        if ($order === null || $order->status !== OrderStatus::PENDING) {
            return $this->error(-31050, 'Order not found or not available', $id);
        }

        $expected = $order->grand_total * 100;
        if ($amount !== $expected) {
            return $this->error(-31001, "Amount mismatch. Expected: {$expected}", $id);
        }

        // Mavjud pending payment ni topamiz yoki yangisini yaratamiz
        $payment = PaymentModel::firstOrCreate(
            ['order_id' => $orderId, 'provider' => 'payme', 'status' => PaymentStatus::PENDING->value],
            ['amount' => $expected]
        );

        $payment->update([
            'transaction_id'          => $transactionId,
            'provider_transaction_id' => $transactionId,
            'payload'                 => $params,
        ]);

        return $this->result([
            'create_time' => now()->getTimestampMs(),
            'transaction' => (string) $payment->id,
            'state'       => 1,
        ], $id);
    }

    private function performTransaction(array $params, mixed $id): array
    {
        $transactionId = (string) ($params['id'] ?? '');

        $payment = PaymentModel::where('transaction_id', $transactionId)->first();

        if ($payment === null) {
            return $this->error(-31050, 'Transaction not found', $id);
        }

        // Idempotency — allaqachon paid
        if ($payment->status === PaymentStatus::PAID) {
            return $this->result([
                'perform_time' => $payment->updated_at->getTimestampMs(),
                'transaction'  => (string) $payment->id,
                'state'        => 2,
            ], $id);
        }

        $this->markPaidHandler->handle(new MarkPaymentPaidCommand(
            orderId:       $payment->order_id,
            transactionId: $transactionId,
            amount:        $payment->amount,
            provider:      'payme',
        ));

        return $this->result([
            'perform_time' => now()->getTimestampMs(),
            'transaction'  => (string) $payment->id,
            'state'        => 2,
        ], $id);
    }

    private function cancelTransaction(array $params, mixed $id): array
    {
        $transactionId = (string) ($params['id'] ?? '');

        $payment = PaymentModel::where('transaction_id', $transactionId)->first();

        if ($payment === null) {
            return $this->error(-31050, 'Transaction not found', $id);
        }

        $state = ($payment->status === PaymentStatus::PAID) ? -2 : -1;

        $payment->update(['status' => PaymentStatus::CANCELLED->value]);

        // Order hali pending bo'lsa cancelled ga o'tkazamiz
        $order = OrderModel::find($payment->order_id);
        if ($order !== null && $order->status === OrderStatus::PENDING) {
            $order->update(['status' => OrderStatus::CANCELLED->value]);
        }

        return $this->result([
            'cancel_time' => now()->getTimestampMs(),
            'transaction' => (string) $payment->id,
            'state'       => $state,
        ], $id);
    }

    private function checkTransaction(array $params, mixed $id): array
    {
        $transactionId = (string) ($params['id'] ?? '');

        $payment = PaymentModel::where('transaction_id', $transactionId)->first();

        if ($payment === null) {
            return $this->error(-31050, 'Transaction not found', $id);
        }

        $state = match ($payment->status) {
            PaymentStatus::PENDING   => 1,
            PaymentStatus::PAID      => 2,
            PaymentStatus::CANCELLED => -1,
            default                  => -1,
        };

        return $this->result([
            'create_time'  => $payment->created_at->getTimestampMs(),
            'perform_time' => $payment->status === PaymentStatus::PAID
                ? $payment->updated_at->getTimestampMs()
                : 0,
            'cancel_time'  => $payment->status === PaymentStatus::CANCELLED
                ? $payment->updated_at->getTimestampMs()
                : 0,
            'transaction'  => (string) $payment->id,
            'state'        => $state,
            'reason'       => null,
        ], $id);
    }

    private function getStatement(array $params, mixed $id): array
    {
        $from = (int) ($params['from'] ?? 0);
        $to   = (int) ($params['to'] ?? 0);

        $payments = PaymentModel::where('provider', 'payme')
            ->whereBetween('created_at', [
                \Carbon\Carbon::createFromTimestampMs($from),
                \Carbon\Carbon::createFromTimestampMs($to),
            ])
            ->get();

        $transactions = $payments->map(fn (PaymentModel $p) => [
            'id'           => $p->transaction_id,
            'time'         => $p->created_at->getTimestampMs(),
            'amount'       => $p->amount,
            'account'      => ['order_id' => $p->order_id],
            'create_time'  => $p->created_at->getTimestampMs(),
            'perform_time' => $p->status === PaymentStatus::PAID ? $p->updated_at->getTimestampMs() : 0,
            'cancel_time'  => $p->status === PaymentStatus::CANCELLED ? $p->updated_at->getTimestampMs() : 0,
            'transaction'  => (string) $p->id,
            'state'        => match ($p->status) {
                PaymentStatus::PENDING   => 1,
                PaymentStatus::PAID      => 2,
                PaymentStatus::CANCELLED => -1,
                default                  => -1,
            },
            'reason' => null,
        ])->values()->all();

        return $this->result(['transactions' => $transactions], $id);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function result(array $data, mixed $id): array
    {
        return ['result' => $data, 'id' => $id];
    }

    private function error(int $code, string $message, mixed $id): array
    {
        return [
            'error' => ['code' => $code, 'message' => $message, 'data' => null],
            'id'    => $id,
        ];
    }
}
