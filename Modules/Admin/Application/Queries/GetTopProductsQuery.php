<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Queries;

final readonly class GetTopProductsQuery
{
    public function __construct(
        public int $limit = 10,
    ) {}
}
