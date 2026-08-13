<?php

declare(strict_types=1);

namespace Modules\Deal\Application\Queries;

final readonly class GetOutgoingDealsQuery
{
    public function __construct(
        public int $buyerId,
        public int $perPage = 20,
    ) {}
}
