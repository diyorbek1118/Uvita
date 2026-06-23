<?php

declare(strict_types=1);

namespace Modules\Order\Application\Commands;

final readonly class NotFoundCommand
{
    public function __construct(
        public int    $orderId,
        public string $reason,
    ) {}
}
