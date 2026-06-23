<?php

declare(strict_types=1);

namespace Modules\Review\Infrastructure\Persistence\Repositories;

use Modules\Review\Domain\Entities\Review;
use Modules\Review\Domain\Enums\ReviewStatus;
use Modules\Review\Domain\Repositories\ReviewRepositoryInterface;
use Modules\Review\Infrastructure\Persistence\Models\ReviewModel;

final class EloquentReviewRepository implements ReviewRepositoryInterface
{
    public function findById(int $id): ?Review
    {
        $model = ReviewModel::find($id);

        return $model !== null ? $this->toDomain($model) : null;
    }

    public function findByOrderId(int $orderId): ?Review
    {
        $model = ReviewModel::where('order_id', $orderId)->first();

        return $model !== null ? $this->toDomain($model) : null;
    }

    public function save(Review $review): int
    {
        if ($review->id === null) {
            $model = ReviewModel::create([
                'order_id'   => $review->orderId,
                'user_id'    => $review->userId,
                'product_id' => $review->productId,
                'rating'     => $review->rating,
                'comment'    => $review->comment,
                'status'     => $review->status->value,
                'is_visible' => $review->isVisible,
                'admin_note' => $review->adminNote,
            ]);

            return $model->id;
        }

        ReviewModel::where('id', $review->id)->update([
            'rating'     => $review->rating,
            'comment'    => $review->comment,
            'status'     => $review->status->value,
            'is_visible' => $review->isVisible,
            'admin_note' => $review->adminNote,
        ]);

        return $review->id;
    }

    private function toDomain(ReviewModel $model): Review
    {
        return new Review(
            id:        $model->id,
            orderId:   $model->order_id,
            userId:    $model->user_id,
            productId: $model->product_id,
            rating:    $model->rating,
            comment:   $model->comment,
            status:    ReviewStatus::from($model->status->value),
            isVisible: $model->is_visible,
            adminNote: $model->admin_note,
        );
    }
}
