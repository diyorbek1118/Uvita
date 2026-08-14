<?php

declare(strict_types=1);

namespace Modules\Seller\Domain\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SellerOrderReadRepositoryInterface
{
    public function paginateForSeller(int $sellerId, int $perPage, ?int $sellerProfileId = null): LengthAwarePaginator;
}
