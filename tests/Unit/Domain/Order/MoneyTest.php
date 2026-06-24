<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Order;

use Modules\Order\Domain\ValueObjects\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_amount_is_stored_correctly(): void
    {
        $money = new Money(15000);

        $this->assertSame(15000, $money->amount);
    }

    public function test_add_returns_new_money_with_sum(): void
    {
        $a = new Money(10000);
        $b = new Money(5000);

        $result = $a->add($b);

        $this->assertSame(15000, $result->amount);
    }

    public function test_add_does_not_mutate_original(): void
    {
        $a = new Money(10000);
        $b = new Money(5000);

        $a->add($b);

        $this->assertSame(10000, $a->amount);
    }

    public function test_multiply_returns_new_money(): void
    {
        $money = new Money(3000);

        $result = $money->multiply(4);

        $this->assertSame(12000, $result->amount);
    }

    public function test_multiply_by_zero_returns_zero(): void
    {
        $money = new Money(9999);

        $result = $money->multiply(0);

        $this->assertSame(0, $result->amount);
    }

    public function test_multiply_does_not_mutate_original(): void
    {
        $money = new Money(3000);

        $money->multiply(5);

        $this->assertSame(3000, $money->amount);
    }

    public function test_add_two_zero_amounts(): void
    {
        $result = (new Money(0))->add(new Money(0));

        $this->assertSame(0, $result->amount);
    }
}
