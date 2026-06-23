<?php

declare(strict_types=1);

namespace Modules\Review\Application\DTOs;

use Modules\Review\Presentation\Requests\UpdateReviewRequest;

final readonly class UpdateReviewDTO
{
    public function __construct(
        public int     $rating,
        public ?string $comment,
    ) {}

    public static function fromRequest(UpdateReviewRequest $request): static
    {
        return new static(
            rating:  (int) $request->input('rating'),
            comment: $request->input('comment'),
        );
    }
}
