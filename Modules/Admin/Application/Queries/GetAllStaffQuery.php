<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Queries;

final readonly class GetAllStaffQuery
{
    public function __construct(
        public ?string $role = null,
    ) {}
}
