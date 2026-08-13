<?php

declare(strict_types=1);

namespace Modules\Courier\Application\Commands;

final readonly class UpdateCourierProfileCommand
{
    public function __construct(
        public int $courierId,
        public string $name,
        public ?string $phone,
        public ?string $vehicleType,
        public ?string $vehicleNumber,
        public ?string $photo,
    ) {}
}
