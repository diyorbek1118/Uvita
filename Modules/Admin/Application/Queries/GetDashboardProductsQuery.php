<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Queries;

final readonly class GetDashboardProductsQuery
{
    public function __construct(
        public ?int    $managerId  = null,  // null → hammasi (admin/super); aks holda manager scope
        public ?string $status     = null,
        public ?int    $categoryId = null,
        public ?string $search     = null,
        public ?int    $maxStock   = null,  // low-stock filtri uchun
        public int     $perPage    = 20,
    ) {}
}
