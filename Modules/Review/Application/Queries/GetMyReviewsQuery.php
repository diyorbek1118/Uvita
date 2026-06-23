<?php

declare(strict_types=1);

namespace Modules\Review\Application\Queries;

final readonly class GetMyReviewsQuery
{
    public function __construct(public int $userId) {}
}
