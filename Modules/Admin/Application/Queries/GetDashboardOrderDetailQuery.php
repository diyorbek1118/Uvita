<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Queries;

final readonly class GetDashboardOrderDetailQuery
{
    public function __construct(
        public int  $id,
        public bool $managerScope = false,
    ) {}
}
