<?php

declare(strict_types=1);

namespace Modules\Review\Application\Commands;

use Modules\Review\Application\DTOs\UpdateReviewDTO;

final readonly class UpdateReviewCommand
{
    public function __construct(
        public int             $reviewId,
        public int             $userId,
        public UpdateReviewDTO $dto,
    ) {}
}
