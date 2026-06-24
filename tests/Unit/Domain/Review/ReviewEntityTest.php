<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Review;

use Modules\Review\Domain\Entities\Review;
use Modules\Review\Domain\Enums\ReviewStatus;
use PHPUnit\Framework\TestCase;

class ReviewEntityTest extends TestCase
{
    private function makeReview(ReviewStatus $status = ReviewStatus::PENDING, bool $isVisible = false): Review
    {
        return new Review(
            id:        1,
            orderId:   10,
            userId:    5,
            productId: 3,
            rating:    4,
            comment:   'Yaxshi mahsulot',
            status:    $status,
            isVisible: $isVisible,
            adminNote: null,
        );
    }

    // ─── approve ──────────────────────────────────────────────────────────────

    public function test_approve_sets_approved_status(): void
    {
        $review = $this->makeReview();
        $review->approve();

        $this->assertSame(ReviewStatus::APPROVED, $review->status);
    }

    public function test_approve_makes_review_visible(): void
    {
        $review = $this->makeReview(isVisible: false);
        $review->approve();

        $this->assertTrue($review->isVisible);
    }

    // ─── reject ───────────────────────────────────────────────────────────────

    public function test_reject_sets_rejected_status(): void
    {
        $review = $this->makeReview();
        $review->reject('Munosib emas');

        $this->assertSame(ReviewStatus::REJECTED, $review->status);
    }

    public function test_reject_hides_review(): void
    {
        $review = $this->makeReview(isVisible: true);
        $review->reject('Spam');

        $this->assertFalse($review->isVisible);
    }

    public function test_reject_sets_admin_note(): void
    {
        $review = $this->makeReview();
        $review->reject('Spam xabar');

        $this->assertSame('Spam xabar', $review->adminNote);
    }

    // ─── update ───────────────────────────────────────────────────────────────

    public function test_update_changes_rating_and_comment(): void
    {
        $review = $this->makeReview();
        $review->update(rating: 5, comment: 'Ajoyib!');

        $this->assertSame(5, $review->rating);
        $this->assertSame('Ajoyib!', $review->comment);
    }

    public function test_update_resets_status_to_pending(): void
    {
        $review = $this->makeReview(ReviewStatus::APPROVED);
        $review->update(rating: 3, comment: null);

        $this->assertSame(ReviewStatus::PENDING, $review->status);
    }

    public function test_update_hides_review(): void
    {
        $review = $this->makeReview(isVisible: true);
        $review->update(rating: 3, comment: null);

        $this->assertFalse($review->isVisible);
    }

    public function test_update_with_null_comment(): void
    {
        $review = $this->makeReview();
        $review->update(rating: 2, comment: null);

        $this->assertNull($review->comment);
    }
}
