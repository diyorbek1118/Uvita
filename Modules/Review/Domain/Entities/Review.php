<?php

declare(strict_types=1);

namespace Modules\Review\Domain\Entities;

use Modules\Review\Domain\Enums\ReviewStatus;

final class Review
{
    public private(set) int $rating;
    public private(set) ?string $comment;
    public private(set) ReviewStatus $status;
    public private(set) bool $isVisible;
    public private(set) ?string $adminNote;

    public function __construct(
        public readonly ?int $id,
        public readonly int  $orderId,
        public readonly int  $userId,
        public readonly int  $productId,
        int                  $rating,
        ?string              $comment,
        ReviewStatus         $status    = ReviewStatus::PENDING,
        bool                 $isVisible = false,
        ?string              $adminNote = null,
    ) {
        $this->rating    = $rating;
        $this->comment   = $comment;
        $this->status    = $status;
        $this->isVisible = $isVisible;
        $this->adminNote = $adminNote;
    }

    public function approve(): void
    {
        $this->status    = ReviewStatus::APPROVED;
        $this->isVisible = true;
    }

    public function reject(string $reason): void
    {
        $this->status    = ReviewStatus::REJECTED;
        $this->isVisible = false;
        $this->adminNote = $reason;
    }

    public function update(int $rating, ?string $comment): void
    {
        $this->rating    = $rating;
        $this->comment   = $comment;
        $this->status    = ReviewStatus::PENDING;
        $this->isVisible = false;
    }
}
