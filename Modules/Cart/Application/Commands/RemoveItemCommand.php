<?php

declare(strict_types=1);

namespace Modules\Cart\Application\Commands;

use Illuminate\Http\Request;
use Modules\Cart\Application\DTOs\RemoveItemDTO;

final readonly class RemoveItemCommand
{
    public function __construct(
        public int           $userId,
        public RemoveItemDTO $dto,
    ) {}

    public static function fromRequest(Request $request, int $userId): self
    {
        return new self(
            userId: $userId,
            dto:    RemoveItemDTO::fromRequest($request),
        );
    }
}
