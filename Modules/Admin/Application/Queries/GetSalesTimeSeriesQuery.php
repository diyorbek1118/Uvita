<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Queries;

final readonly class GetSalesTimeSeriesQuery
{
    public function __construct(
        public string  $period = 'daily',  // daily | weekly | monthly
        public ?string $from   = null,
        public ?string $to     = null,
    ) {}
}
