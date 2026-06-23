<?php

declare(strict_types=1);

namespace Modules\Cart\Application\DTOs;

use Illuminate\Http\Request;

final readonly class AddItemDTO
{
    public function __construct(
        public int $productId,
        public int $quantity,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            productId: (int) $request->validated('product_id'),
            quantity:  (int) $request->validated('quantity'),
        );
    }
}
