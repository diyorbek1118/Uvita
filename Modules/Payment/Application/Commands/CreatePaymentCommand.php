<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Commands;

final readonly class CreatePaymentCommand
{
    public function __construct(
        public int    $orderId,
        public int    $amount,   // tiyinda (grand_total * 100)
        public string $provider, // 'payme' | 'click' | 'uzum'
    ) {}
}
