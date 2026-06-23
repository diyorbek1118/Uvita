<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\Repositories;

use Modules\Payment\Domain\Entities\Payment;

interface PaymentRepositoryInterface
{
    public function findByOrderId(int $orderId): ?Payment;
    public function findByTransactionId(string $txId): ?Payment;
    public function save(Payment $payment): void;
}
