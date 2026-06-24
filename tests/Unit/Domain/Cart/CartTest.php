<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Cart;

use Modules\Cart\Domain\Entities\Cart;
use Modules\Cart\Domain\Entities\CartItem;
use Modules\Cart\Domain\Exceptions\CartItemNotFoundException;
use Modules\Cart\Domain\Exceptions\InsufficientStockException;
use PHPUnit\Framework\TestCase;

class CartTest extends TestCase
{
    private function makeItem(int $productId, int $quantity, int $price = 10000, ?int $id = null): CartItem
    {
        return new CartItem(
            id:        $id,
            cartId:    1,
            productId: $productId,
            quantity:  $quantity,
            price:     $price,
        );
    }

    private function makeCart(array $items = []): Cart
    {
        return new Cart(id: 1, userId: 10, items: $items);
    }

    // ─── addItem ──────────────────────────────────────────────────────────────

    public function test_add_new_item_to_empty_cart(): void
    {
        $cart = $this->makeCart();
        $cart->addItem($this->makeItem(productId: 5, quantity: 2), availableStock: 10);

        $this->assertCount(1, $cart->items);
        $this->assertSame(5, $cart->items[0]->productId);
        $this->assertSame(2, $cart->items[0]->quantity);
    }

    public function test_add_existing_product_merges_quantity(): void
    {
        $cart = $this->makeCart([
            $this->makeItem(productId: 5, quantity: 3),
        ]);

        $cart->addItem($this->makeItem(productId: 5, quantity: 2), availableStock: 10);

        $this->assertCount(1, $cart->items);
        $this->assertSame(5, $cart->items[0]->quantity);
    }

    public function test_add_different_products_creates_separate_items(): void
    {
        $cart = $this->makeCart();
        $cart->addItem($this->makeItem(productId: 1, quantity: 1), availableStock: 5);
        $cart->addItem($this->makeItem(productId: 2, quantity: 2), availableStock: 5);

        $this->assertCount(2, $cart->items);
    }

    public function test_add_item_exceeding_stock_throws(): void
    {
        $this->expectException(InsufficientStockException::class);

        $cart = $this->makeCart();
        $cart->addItem($this->makeItem(productId: 1, quantity: 10), availableStock: 5);
    }

    public function test_merge_quantity_exceeding_stock_throws(): void
    {
        $this->expectException(InsufficientStockException::class);

        $cart = $this->makeCart([
            $this->makeItem(productId: 5, quantity: 8),
        ]);

        $cart->addItem($this->makeItem(productId: 5, quantity: 5), availableStock: 10);
    }

    public function test_add_item_exactly_at_stock_limit_is_allowed(): void
    {
        $cart = $this->makeCart();
        $cart->addItem($this->makeItem(productId: 1, quantity: 5), availableStock: 5);

        $this->assertSame(5, $cart->items[0]->quantity);
    }

    // ─── removeItem ───────────────────────────────────────────────────────────

    public function test_remove_existing_item(): void
    {
        $cart = $this->makeCart([
            $this->makeItem(productId: 1, quantity: 2),
            $this->makeItem(productId: 2, quantity: 3),
        ]);

        $cart->removeItem(1);

        $this->assertCount(1, $cart->items);
        $this->assertSame(2, $cart->items[0]->productId);
    }

    public function test_remove_non_existent_item_throws(): void
    {
        $this->expectException(CartItemNotFoundException::class);

        $cart = $this->makeCart();
        $cart->removeItem(999);
    }

    public function test_remove_item_reindexes_array(): void
    {
        $cart = $this->makeCart([
            $this->makeItem(productId: 1, quantity: 1),
            $this->makeItem(productId: 2, quantity: 1),
            $this->makeItem(productId: 3, quantity: 1),
        ]);

        $cart->removeItem(2);

        $this->assertSame(0, array_key_first($cart->items));
        $this->assertCount(2, $cart->items);
    }

    // ─── clear ────────────────────────────────────────────────────────────────

    public function test_clear_empties_cart(): void
    {
        $cart = $this->makeCart([
            $this->makeItem(productId: 1, quantity: 2),
            $this->makeItem(productId: 2, quantity: 3),
        ]);

        $cart->clear();

        $this->assertCount(0, $cart->items);
    }

    public function test_clear_on_empty_cart_is_safe(): void
    {
        $cart = $this->makeCart();
        $cart->clear();

        $this->assertCount(0, $cart->items);
    }

    // ─── totalPrice ───────────────────────────────────────────────────────────

    public function test_total_price_sums_all_items(): void
    {
        $cart = $this->makeCart([
            $this->makeItem(productId: 1, quantity: 2, price: 10000),
            $this->makeItem(productId: 2, quantity: 3, price: 5000),
        ]);

        // 2*10000 + 3*5000 = 20000 + 15000 = 35000
        $this->assertSame(35000, $cart->totalPrice());
    }

    public function test_total_price_of_empty_cart_is_zero(): void
    {
        $cart = $this->makeCart();

        $this->assertSame(0, $cart->totalPrice());
    }
}
