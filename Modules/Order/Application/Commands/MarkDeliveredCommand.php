<?php

declare(strict_types=1);

namespace Modules\Order\Application\Commands;

final readonly class MarkDeliveredCommand
{
    public function __construct(
        public int $orderId,
        public int $courierId,
        public string $pin,
        public ?string $recipientName = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public ?int $cashReceived = null,
    ) {}
}
