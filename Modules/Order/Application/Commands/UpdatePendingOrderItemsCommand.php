<?php

declare(strict_types=1);

namespace Modules\Order\Application\Commands;

final readonly class UpdatePendingOrderItemsCommand
{
    /** @param array<int, array{product_id: int, quantity: int}> $items */
    public function __construct(
        public int $orderId,
        public array $items,
    ) {}
}
