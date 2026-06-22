<?php

declare(strict_types=1);

namespace Modules\Category\Application\Commands;

final readonly class DeleteCategoryCommand
{
    public function __construct(
        public int $id,
    ) {}
}
