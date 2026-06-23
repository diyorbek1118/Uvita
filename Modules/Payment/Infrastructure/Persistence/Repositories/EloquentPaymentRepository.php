<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Persistence\Repositories;

use Modules\Payment\Domain\Entities\Payment;
use Modules\Payment\Domain\Enums\PaymentProvider;
use Modules\Payment\Domain\Enums\PaymentStatus;
use Modules\Payment\Domain\Repositories\PaymentRepositoryInterface;
use Modules\Payment\Infrastructure\Persistence\Models\PaymentModel;

final class EloquentPaymentRepository implements PaymentRepositoryInterface
{
    public function findByOrderId(int $orderId): ?Payment
    {
        $model = PaymentModel::where('order_id', $orderId)
            ->latest()
            ->first();

        return $model !== null ? $this->toDomain($model) : null;
    }

    public function findByTransactionId(string $txId): ?Payment
    {
        $model = PaymentModel::where('transaction_id', $txId)->first();

        return $model !== null ? $this->toDomain($model) : null;
    }

    public function save(Payment $payment): void
    {
        if ($payment->id === null) {
            PaymentModel::create([
                'order_id'       => $payment->orderId,
                'provider'       => $payment->provider->value,
                'transaction_id' => $payment->transactionId,
                'amount'         => $payment->amount,
                'status'         => $payment->status->value,
            ]);
        } else {
            PaymentModel::where('id', $payment->id)->update([
                'status'         => $payment->status->value,
                'transaction_id' => $payment->transactionId,
            ]);
        }
    }

    private function toDomain(PaymentModel $model): Payment
    {
        return new Payment(
            id:            $model->id,
            orderId:       $model->order_id,
            provider:      PaymentProvider::from($model->provider->value),
            transactionId: $model->transaction_id,
            amount:        $model->amount,
            status:        PaymentStatus::from($model->status->value),
        );
    }
}
