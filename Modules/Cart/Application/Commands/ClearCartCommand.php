<?php

declare(strict_types=1);

namespace Modules\Cart\Application\Commands;

final readonly class ClearCartCommand
{
    public function __construct(
        public int $userId,
    ) {}
}
