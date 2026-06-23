<?php

declare(strict_types=1);

namespace Modules\Review\Application\Commands;

use Modules\Review\Application\DTOs\CreateReviewDTO;
use Modules\Review\Presentation\Requests\CreateReviewRequest;

final readonly class CreateReviewCommand
{
    public function __construct(public CreateReviewDTO $dto) {}

    public static function fromRequest(CreateReviewRequest $request, int $userId): static
    {
        return new static(dto: CreateReviewDTO::fromRequest($request, $userId));
    }
}
