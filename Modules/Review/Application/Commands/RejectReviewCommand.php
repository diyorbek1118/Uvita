<?php

declare(strict_types=1);

namespace Modules\Review\Application\Commands;

final readonly class RejectReviewCommand
{
    public function __construct(
        public int    $reviewId,
        public string $reason,
    ) {}
}
