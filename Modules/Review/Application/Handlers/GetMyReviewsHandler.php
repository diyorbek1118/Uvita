<?php

declare(strict_types=1);

namespace Modules\Review\Application\Handlers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Review\Application\Queries\GetMyReviewsQuery;
use Modules\Review\Infrastructure\Persistence\Models\ReviewModel;

final class GetMyReviewsHandler
{
    public function handle(GetMyReviewsQuery $query): LengthAwarePaginator
    {
        return ReviewModel::where('user_id', $query->userId)
            ->latest()
            ->paginate(15);
    }
}
