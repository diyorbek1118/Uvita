<?php

declare(strict_types=1);

namespace Modules\Cart\Domain\Entities;

final readonly class CartItem
{
    public function __construct(
        public ?int $id,
        public ?int $cartId,
        public int  $productId,
        public int  $quantity,
        public int  $price,
    ) {}
}
