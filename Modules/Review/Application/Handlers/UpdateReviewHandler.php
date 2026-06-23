<?php

declare(strict_types=1);

namespace Modules\Review\Application\Handlers;

use Modules\Review\Application\Commands\UpdateReviewCommand;
use Modules\Review\Domain\Exceptions\ReviewNotFoundException;
use Modules\Review\Domain\Repositories\ReviewRepositoryInterface;
use Modules\Review\Infrastructure\Persistence\Models\ReviewModel;

final class UpdateReviewHandler
{
    public function __construct(
        private readonly ReviewRepositoryInterface $reviews,
    ) {}

    public function handle(UpdateReviewCommand $command): ReviewModel
    {
        $review = $this->reviews->findById($command->reviewId);

        if ($review === null) {
            throw new ReviewNotFoundException("Sharh topilmadi.");
        }

        if ($review->userId !== $command->userId) {
            abort(403, "Bu sharh sizga tegishli emas.");
        }

        $review->update($command->dto->rating, $command->dto->comment);

        $this->reviews->save($review);

        return ReviewModel::findOrFail($command->reviewId);
    }
}
