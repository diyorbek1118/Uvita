<?php

declare(strict_types=1);

namespace Modules\Order\Application\Commands;

final readonly class ConfirmOrderCommand
{
    public function __construct(public int $orderId) {}
}
