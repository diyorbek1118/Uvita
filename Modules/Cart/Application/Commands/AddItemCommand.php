<?php

declare(strict_types=1);

namespace Modules\Cart\Application\Commands;

use Illuminate\Http\Request;
use Modules\Cart\Application\DTOs\AddItemDTO;

final readonly class AddItemCommand
{
    public function __construct(
        public int        $userId,
        public AddItemDTO $dto,
    ) {}

    public static function fromRequest(Request $request, int $userId): self
    {
        return new self(
            userId: $userId,
            dto:    AddItemDTO::fromRequest($request),
        );
    }
}
