<?php

declare(strict_types=1);

namespace Modules\Order\Domain\Entities;

use Modules\Order\Domain\ValueObjects\Money;

final readonly class OrderItem
{
    public function __construct(
        public ?int  $id,
        public ?int  $orderId,
        public int   $productId,
        public int   $quantity,
        public Money $price,
    ) {}

    public function subtotal(): Money
    {
        return $this->price->multiply($this->quantity);
    }
}
