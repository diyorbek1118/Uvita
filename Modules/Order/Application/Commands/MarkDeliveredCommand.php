<?php

declare(strict_types=1);

namespace Modules\Order\Application\Commands;

final readonly class MarkDeliveredCommand
{
    public function __construct(public int $orderId) {}
}
