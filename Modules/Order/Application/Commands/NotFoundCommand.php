<?php

declare(strict_types=1);

namespace Modules\Order\Application\Commands;

final readonly class NotFoundCommand
{
    public function __construct(
        public int $orderId,
        public int $courierId,
        public string $reasonCode,
        public ?string $reasonNote = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
    ) {}
}
