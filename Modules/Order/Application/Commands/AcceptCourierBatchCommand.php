<?php

declare(strict_types=1);

namespace Modules\Order\Application\Commands;

final readonly class AcceptCourierBatchCommand
{
    /** @param array<int, int> $orderIds */
    public function __construct(
        public int $courierId,
        public array $orderIds,
    ) {}
}
