<?php

declare(strict_types=1);

namespace Modules\Listing\Application\Queries;

final readonly class GetMyListingsQuery
{
    public function __construct(
        public int $sellerId,
        public int $perPage = 20,
    ) {}
}
