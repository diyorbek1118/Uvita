<?php

declare(strict_types=1);

namespace Modules\Deal\Application\Queries;

final readonly class GetIncomingDealsQuery
{
    public function __construct(
        public int $sellerId,
        public int $perPage = 20,
    ) {}
}
