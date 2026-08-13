<?php

declare(strict_types=1);

namespace Modules\Listing\Application\Handlers;

use Illuminate\Contracts\Pagination\Paginator;
use Modules\Listing\Application\Queries\GetListingListQuery;
use Modules\Listing\Domain\Repositories\ListingRepositoryInterface;

final class GetListingListHandler
{
    public function __construct(
        private readonly ListingRepositoryInterface $listings,
    ) {}

    public function handle(GetListingListQuery $query): Paginator
    {
        return $this->listings->paginateActive($query->filters, $query->perPage);
    }
}
