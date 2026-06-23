<?php

declare(strict_types=1);

namespace Modules\Review\Application\Handlers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Review\Application\Queries\GetProductReviewsQuery;
use Modules\Review\Infrastructure\Persistence\Models\ReviewModel;

final class GetProductReviewsHandler
{
    public function handle(GetProductReviewsQuery $query): LengthAwarePaginator
    {
        return ReviewModel::where('product_id', $query->productId)
            ->where('status', 'approved')
            ->where('is_visible', true)
            ->latest()
            ->paginate(10);
    }
}
