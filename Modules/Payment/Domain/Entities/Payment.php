<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\Entities;

use Modules\Payment\Domain\Enums\PaymentProvider;
use Modules\Payment\Domain\Enums\PaymentStatus;

class Payment
{
    public private(set) PaymentStatus $status;
    public private(set) ?string $transactionId;

    public function __construct(
        public readonly ?int     $id,
        public readonly int      $orderId,
        public readonly PaymentProvider $provider,
        ?string                  $transactionId,
        public readonly int      $amount,
        PaymentStatus            $status = PaymentStatus::PENDING,
    ) {
        $this->status        = $status;
        $this->transactionId = $transactionId;
    }

    public function markAsPaid(string $transactionId): void
    {
        $this->status        = PaymentStatus::PAID;
        $this->transactionId = $transactionId;
    }

    public function markAsFailed(): void
    {
        $this->status = PaymentStatus::FAILED;
    }

    public function markAsCancelled(): void
    {
        $this->status = PaymentStatus::CANCELLED;
    }
}
