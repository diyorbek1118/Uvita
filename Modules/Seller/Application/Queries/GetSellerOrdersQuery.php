<?php

declare(strict_types=1);

namespace Modules\Seller\Application\Queries;

final readonly class GetSellerOrdersQuery
{
    public function __construct(
        public int $sellerId,
        public ?int $sellerProfileId = null,
        public int $perPage = 30,
    ) {}
}
