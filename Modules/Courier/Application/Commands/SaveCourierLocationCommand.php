<?php

declare(strict_types=1);

namespace Modules\Courier\Application\Commands;

final readonly class SaveCourierLocationCommand
{
    public function __construct(
        public int $courierId,
        public int $orderId,
        public float $latitude,
        public float $longitude,
        public ?int $accuracy,
        public ?string $recordedAt,
    ) {}
}
