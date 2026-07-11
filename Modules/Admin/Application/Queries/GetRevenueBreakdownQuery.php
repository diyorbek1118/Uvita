<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Queries;

final readonly class GetRevenueBreakdownQuery
{
    public function __construct(
        public ?string $from = null,
        public ?string $to   = null,
    ) {}
}
