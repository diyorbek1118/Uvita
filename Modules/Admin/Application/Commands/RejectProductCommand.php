<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Commands;

final readonly class RejectProductCommand
{
    public function __construct(
        public int    $id,
        public string $reason,
    ) {}
}
