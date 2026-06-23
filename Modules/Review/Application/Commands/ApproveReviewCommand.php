<?php

declare(strict_types=1);

namespace Modules\Review\Application\Commands;

final readonly class ApproveReviewCommand
{
    public function __construct(public int $reviewId) {}
}
