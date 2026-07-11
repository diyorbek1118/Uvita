<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Queries;

final readonly class GetDashboardProductByIdQuery
{
    public function __construct(
        public int  $id,
        public ?int $managerId = null,  // manager scope; null → istalgan mahsulot (admin/super)
    ) {}
}
