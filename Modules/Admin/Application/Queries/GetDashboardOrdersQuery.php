<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Queries;

final readonly class GetDashboardOrdersQuery
{
    public function __construct(
        public bool    $managerScope = false,  // true → manager (faqat paid+ ko'radi)
        public ?string $status       = null,
        public ?string $search       = null,   // telefon yoki customer ismi
        public ?string $dateFrom     = null,
        public ?string $dateTo       = null,
        public int     $perPage      = 20,
    ) {}
}
