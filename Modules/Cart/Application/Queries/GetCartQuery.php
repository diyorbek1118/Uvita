<?php

declare(strict_types=1);

namespace Modules\Cart\Application\Queries;

final readonly class GetCartQuery
{
    public function __construct(
        public int $userId,
    ) {}
}
