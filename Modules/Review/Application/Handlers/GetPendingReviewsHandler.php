<?php

declare(strict_types=1);

namespace Modules\Review\Application\Handlers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Review\Application\Queries\GetPendingReviewsQuery;
use Modules\Review\Infrastructure\Persistence\Models\ReviewModel;

final class GetPendingReviewsHandler
{
    public function handle(GetPendingReviewsQuery $query): LengthAwarePaginator
    {
        return ReviewModel::where('status', 'pending')
            ->with('user')
            ->latest()
            ->paginate(20);
    }
}
