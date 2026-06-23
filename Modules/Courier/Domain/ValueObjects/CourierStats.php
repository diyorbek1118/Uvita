<?php

declare(strict_types=1);

namespace Modules\Courier\Domain\ValueObjects;

final readonly class CourierStats
{
    public float $successRate;

    public function __construct(
        public int $totalDelivered,
        public int $totalNotFound,
        public int $totalActive,
    ) {
        $total = $this->totalDelivered + $this->totalNotFound;
        $this->successRate = $total > 0
            ? round($this->totalDelivered / $total * 100, 1)
            : 0.0;
    }
}
