<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Order;

use Modules\Order\Domain\Entities\OrderItem;
use Modules\Order\Domain\ValueObjects\Money;
use PHPUnit\Framework\TestCase;

class OrderItemTest extends TestCase
{
    public function test_subtotal_is_price_times_quantity(): void
    {
        $item = new OrderItem(
            id:        1,
            orderId:   10,
            productId: 5,
            quantity:  3,
            price:     new Money(20000),
        );

        $this->assertSame(60000, $item->subtotal()->amount);
    }

    public function test_subtotal_with_quantity_one(): void
    {
        $item = new OrderItem(
            id:        null,
            orderId:   null,
            productId: 1,
            quantity:  1,
            price:     new Money(50000),
        );

        $this->assertSame(50000, $item->subtotal()->amount);
    }

    public function test_subtotal_returns_money_instance(): void
    {
        $item = new OrderItem(
            id:        null,
            orderId:   null,
            productId: 1,
            quantity:  2,
            price:     new Money(10000),
        );

        $this->assertInstanceOf(Money::class, $item->subtotal());
    }
}
