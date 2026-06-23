<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Queries;

final readonly class GetCourierByIdQuery
{
    public function __construct(
        public int $courierId,
    ) {}
}
