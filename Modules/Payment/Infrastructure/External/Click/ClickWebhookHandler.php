<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\External\Click;

use Illuminate\Http\Request;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;
use Modules\Payment\Application\Commands\MarkPaymentPaidCommand;
use Modules\Payment\Application\Handlers\MarkPaymentPaidHandler;
use Modules\Payment\Domain\Enums\PaymentStatus;
use Modules\Payment\Infrastructure\Persistence\Models\PaymentModel;

final class ClickWebhookHandler
{
    public function __construct(
        private readonly MarkPaymentPaidHandler $markPaidHandler,
    ) {}

    public function handle(Request $request): array
    {
        $action = (int) $request->input('action', -1);

        // Signature test rejimda tekshirilmaydi (keys bo'sh)
        if (!config('payment.test_mode')) {
            $this->verifySignature($request, $action);
        }

        return match ($action) {
            0 => $this->prepare($request),
            1 => $this->complete($request),
            default => $this->error(-9, 'Unknown action'),
        };
    }

    // ─── Prepare (action=0) ──────────────────────────────────────────────────

    private function prepare(Request $request): array
    {
        $orderId      = (int) $request->input('merchant_trans_id');
        $clickTransId = (string) $request->input('click_trans_id');
        $amountSom    = (float) $request->input('amount'); // Click so'm yuboradi

        $order = OrderModel::find($orderId);

        if ($order === null) {
            return $this->error(-5009, 'Order not found', $clickTransId, $orderId);
        }

        $expectedSom = $order->grand_total;
        if (abs($amountSom - $expectedSom) > 0.01) {
            return $this->error(-5001, 'Amount mismatch', $clickTransId, $orderId);
        }

        // Yangi payment yoki mavjud pending ni topamiz
        $payment = PaymentModel::firstOrCreate(
            ['order_id' => $orderId, 'provider' => 'click', 'status' => PaymentStatus::PENDING->value],
            ['amount' => $order->grand_total * 100] // tiyinlarda saqlaymiz
        );

        return [
            'click_trans_id'      => $clickTransId,
            'merchant_trans_id'   => $orderId,
            'merchant_prepare_id' => $payment->id,
            'error'               => 0,
            'error_note'          => 'Success',
        ];
    }

    // ─── Complete (action=1) ─────────────────────────────────────────────────

    private function complete(Request $request): array
    {
        $merchantPrepareId = (int) $request->input('merchant_prepare_id');
        $clickTransId      = (string) $request->input('click_trans_id');
        $orderId           = (int) $request->input('merchant_trans_id');
        $error             = (int) $request->input('error', 0);

        $payment = PaymentModel::find($merchantPrepareId);

        if ($payment === null) {
            return $this->error(-5010, 'Prepare not found', $clickTransId, $orderId);
        }

        // Idempotency
        if ($payment->status === PaymentStatus::PAID) {
            return $this->success($clickTransId, $orderId, $payment->id);
        }

        // Click xatosi keldi — payment ni cancel qilamiz
        if ($error !== 0) {
            $payment->update(['status' => PaymentStatus::CANCELLED->value]);

            return $this->error(-5007, 'Transaction failed by Click', $clickTransId, $orderId);
        }

        // To'lovni amalga oshiramiz
        $this->markPaidHandler->handle(new MarkPaymentPaidCommand(
            orderId:       $orderId,
            transactionId: $clickTransId,
            amount:        $payment->amount,
            provider:      'click',
        ));

        $payment->update([
            'transaction_id'          => $clickTransId,
            'provider_transaction_id' => $clickTransId,
        ]);

        return $this->success($clickTransId, $orderId, $payment->id);
    }

    // ─── Signature ───────────────────────────────────────────────────────────

    private function verifySignature(Request $request, int $action): void
    {
        $secretKey    = (string) config('payment.click.secret_key', '');
        $serviceId    = (string) $request->input('service_id');
        $clickTransId = (string) $request->input('click_trans_id');
        $merchantId   = (string) $request->input('merchant_trans_id');
        $amount       = (string) $request->input('amount');
        $signTime     = (string) $request->input('sign_time');

        if ($action === 0) {
            $expected = md5($clickTransId . $serviceId . $secretKey . $merchantId . $amount . $action . $signTime);
        } else {
            $prepareId = (string) $request->input('merchant_prepare_id');
            $expected  = md5($clickTransId . $serviceId . $secretKey . $merchantId . $prepareId . $amount . $action . $signTime);
        }

        if ($expected !== $request->input('sign_string')) {
            abort(401, 'Invalid signature');
        }
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function success(string $clickTransId, int $orderId, int $paymentId): array
    {
        return [
            'click_trans_id'     => $clickTransId,
            'merchant_trans_id'  => $orderId,
            'merchant_confirm_id' => $paymentId,
            'error'              => 0,
            'error_note'         => 'Success',
        ];
    }

    private function error(int $code, string $note, string $clickTransId = '', int $orderId = 0): array
    {
        return [
            'click_trans_id'    => $clickTransId,
            'merchant_trans_id' => $orderId,
            'error'             => $code,
            'error_note'        => $note,
        ];
    }
}
