<?php

declare(strict_types=1);

namespace Modules\Review\Application\Handlers;

use Modules\Review\Application\Commands\RejectReviewCommand;
use Modules\Review\Domain\Exceptions\ReviewNotFoundException;
use Modules\Review\Domain\Repositories\ReviewRepositoryInterface;

final class RejectReviewHandler
{
    public function __construct(
        private readonly ReviewRepositoryInterface $reviews,
    ) {}

    public function handle(RejectReviewCommand $command): void
    {
        $review = $this->reviews->findById($command->reviewId);

        if ($review === null) {
            throw new ReviewNotFoundException("Sharh topilmadi.");
        }

        $review->reject($command->reason);

        $this->reviews->save($review);
    }
}
