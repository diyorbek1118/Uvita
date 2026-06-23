<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Queries;

final readonly class GetAllTransactionsQuery
{
    public function __construct(
        public ?string $provider = null,
        public ?string $status   = null,
        public ?string $dateFrom = null,
        public ?string $dateTo   = null,
    ) {}
}
