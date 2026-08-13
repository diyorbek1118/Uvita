<?php

declare(strict_types=1);

namespace Modules\Courier\Application\Commands;

final readonly class SetCourierAvailabilityCommand
{
    public function __construct(public int $courierId, public bool $isOnline) {}
}
