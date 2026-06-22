<?php

declare(strict_types=1);

namespace Modules\Product\Application\Commands;

final readonly class ApproveProductCommand
{
    public function __construct(
        public int $id,
    ) {}
}
