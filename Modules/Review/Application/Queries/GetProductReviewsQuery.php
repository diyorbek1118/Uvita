<?php

declare(strict_types=1);

namespace Modules\Review\Application\Queries;

final readonly class GetProductReviewsQuery
{
    public function __construct(public int $productId) {}
}
