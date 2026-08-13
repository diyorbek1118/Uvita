<?php

declare(strict_types=1);

namespace Modules\Listing\Application\Handlers;

use Illuminate\Contracts\Pagination\Paginator;
use Modules\Listing\Application\Queries\GetMyListingsQuery;
use Modules\Listing\Domain\Repositories\ListingRepositoryInterface;

final class GetMyListingsHandler
{
    public function __construct(
        private readonly ListingRepositoryInterface $listings,
    ) {}

    public function handle(GetMyListingsQuery $query): Paginator
    {
        return $this->listings->paginateBySeller($query->sellerId, $query->perPage);
    }
}
