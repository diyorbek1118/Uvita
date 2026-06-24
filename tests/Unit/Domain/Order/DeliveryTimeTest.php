<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Order;

use InvalidArgumentException;
use Modules\Order\Domain\ValueObjects\DeliveryTime;
use PHPUnit\Framework\TestCase;

class DeliveryTimeTest extends TestCase
{
    public function test_valid_delivery_time_is_stored(): void
    {
        $time = new DeliveryTime('Ertaga 14:00-18:00');

        $this->assertSame('Ertaga 14:00-18:00', $time->value);
    }

    public function test_empty_string_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new DeliveryTime('');
    }

    public function test_whitespace_only_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new DeliveryTime('   ');
    }

    public function test_valid_delivery_time_with_spaces(): void
    {
        $time = new DeliveryTime('Bugun 10:00 - 12:00');

        $this->assertSame('Bugun 10:00 - 12:00', $time->value);
    }
}
