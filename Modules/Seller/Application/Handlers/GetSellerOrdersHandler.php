<?php

declare(strict_types=1);

namespace Modules\Seller\Application\Handlers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Seller\Application\Queries\GetSellerOrdersQuery;
use Modules\Seller\Domain\Repositories\SellerOrderReadRepositoryInterface;

final readonly class GetSellerOrdersHandler
{
    public function __construct(
        private SellerOrderReadRepositoryInterface $orders,
    ) {}

    public function handle(GetSellerOrdersQuery $query): LengthAwarePaginator
    {
        return $this->orders->paginateForSeller(
            $query->sellerId,
            min(max($query->perPage, 1), 100),
            $query->sellerProfileId,
        );
    }
}
