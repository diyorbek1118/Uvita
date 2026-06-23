<?php

declare(strict_types=1);

namespace Modules\Cart\Application\DTOs;

use Illuminate\Http\Request;

final readonly class RemoveItemDTO
{
    public function __construct(
        public int $productId,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            productId: (int) $request->validated('product_id'),
        );
    }
}
