<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Queries;

final readonly class GetStaffByIdQuery
{
    public function __construct(
        public int $staffId,
    ) {}
}
