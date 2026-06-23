<?php

declare(strict_types=1);

namespace Modules\Review\Application\Handlers;

use Modules\Product\Infrastructure\Persistence\Models\Product as ProductModel;
use Modules\Review\Application\Commands\ApproveReviewCommand;
use Modules\Review\Domain\Exceptions\ReviewNotFoundException;
use Modules\Review\Domain\Repositories\ReviewRepositoryInterface;
use Modules\Review\Infrastructure\Persistence\Models\ReviewModel;

final class ApproveReviewHandler
{
    public function __construct(
        private readonly ReviewRepositoryInterface $reviews,
    ) {}

    public function handle(ApproveReviewCommand $command): void
    {
        $review = $this->reviews->findById($command->reviewId);

        if ($review === null) {
            throw new ReviewNotFoundException("Sharh topilmadi.");
        }

        $review->approve();

        $this->reviews->save($review);

        $approved = ReviewModel::where('product_id', $review->productId)
            ->where('status', 'approved')
            ->get();

        ProductModel::where('id', $review->productId)->update([
            'rating'        => round((float) $approved->avg('rating'), 1),
            'reviews_count' => $approved->count(),
        ]);
    }
}
