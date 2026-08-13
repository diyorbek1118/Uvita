<?php

declare(strict_types=1);

namespace Modules\Listing\Application\Queries;

final readonly class GetListingListQuery
{
    public function __construct(
        public array $filters = [],
        public int   $perPage = 20,
    ) {}
}
