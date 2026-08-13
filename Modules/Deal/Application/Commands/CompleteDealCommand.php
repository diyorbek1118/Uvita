<?php

declare(strict_types=1);

namespace Modules\Deal\Application\Commands;

final readonly class CompleteDealCommand
{
    public function __construct(
        public int $dealId,
        public int $userId,
    ) {}
}
