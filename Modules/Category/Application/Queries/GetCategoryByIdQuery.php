<?php

declare(strict_types=1);

namespace Modules\Category\Application\Queries;

final readonly class GetCategoryByIdQuery
{
    public function __construct(
        public int $id,
    ) {}
}
