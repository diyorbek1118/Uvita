<?php

declare(strict_types=1);

namespace Modules\Cart\Domain\Entities;

use Modules\Cart\Domain\Exceptions\CartItemNotFoundException;
use Modules\Cart\Domain\Exceptions\InsufficientStockException;

final class Cart
{
    /** @var CartItem[] */
    public private(set) array $items;

    public function __construct(
        public readonly ?int $id,
        public readonly int  $userId,
        array                $items = [],
    ) {
        $this->items = $items;
    }

    public function addItem(CartItem $newItem, int $availableStock): void
    {
        foreach ($this->items as $key => $existing) {
            if ($existing->productId === $newItem->productId) {
                $merged = $existing->quantity + $newItem->quantity;

                if ($merged > $availableStock) {
                    throw new InsufficientStockException('Omborda yetarli mahsulot yo\'q.');
                }

                $this->items[$key] = new CartItem(
                    id:        $existing->id,
                    cartId:    $existing->cartId,
                    productId: $existing->productId,
                    quantity:  $merged,
                    price:     $newItem->price,
                );

                return;
            }
        }

        if ($newItem->quantity > $availableStock) {
            throw new InsufficientStockException('Omborda yetarli mahsulot yo\'q.');
        }

        $this->items[] = $newItem;
    }

    public function removeItem(int $productId): void
    {
        foreach ($this->items as $key => $item) {
            if ($item->productId === $productId) {
                unset($this->items[$key]);
                $this->items = array_values($this->items);
                return;
            }
        }

        throw new CartItemNotFoundException('Bu mahsulot savatchada topilmadi.');
    }

    public function clear(): void
    {
        $this->items = [];
    }

    public function totalPrice(): int
    {
        return (int) array_sum(
            array_map(fn(CartItem $item) => $item->price * $item->quantity, $this->items)
        );
    }
}
