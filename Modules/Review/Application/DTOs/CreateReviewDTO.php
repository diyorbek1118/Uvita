<?php

declare(strict_types=1);

namespace Modules\Review\Application\DTOs;

use Modules\Review\Presentation\Requests\CreateReviewRequest;

final readonly class CreateReviewDTO
{
    public function __construct(
        public int     $orderId,
        public int     $userId,
        public int     $productId,
        public int     $rating,
        public ?string $comment,
    ) {}

    public static function fromRequest(CreateReviewRequest $request, int $userId): static
    {
        return new static(
            orderId:   (int) $request->input('order_id'),
            userId:    $userId,
            productId: (int) $request->input('product_id'),
            rating:    (int) $request->input('rating'),
            comment:   $request->input('comment'),
        );
    }
}
