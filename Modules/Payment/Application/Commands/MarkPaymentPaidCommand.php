<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Commands;

final readonly class MarkPaymentPaidCommand
{
    public function __construct(
        public int    $orderId,
        public string $transactionId,
        public int    $amount,   // tiyinda
        public string $provider, // 'payme' | 'click' | 'uzum'
    ) {}
}
