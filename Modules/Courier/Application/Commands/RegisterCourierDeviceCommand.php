<?php

declare(strict_types=1);

namespace Modules\Courier\Application\Commands;

final readonly class RegisterCourierDeviceCommand
{
    public function __construct(
        public int $courierId,
        public string $token,
        public string $platform,
        public ?string $deviceName,
    ) {}
}
