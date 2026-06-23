<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Queries;

final readonly class GetAllUsersQuery
{
    public function __construct(
        public ?string $search = null,
    ) {}
}
