<?php

declare(strict_types=1);

namespace Modules\Product\Application\Queries;

final readonly class GetProductByIdQuery
{
    public function __construct(
        public int $id,
    ) {}
}
