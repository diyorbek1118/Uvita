<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Shared\Services\Fee\OrderFeeCalculator;
use PHPUnit\Framework\TestCase;

final class OrderFeeCalculatorTest extends TestCase
{
    public function test_customer_pays_listed_price_and_seller_receives_net_amount(): void
    {
        $result = (new OrderFeeCalculator())->calculate(100_000);

        $this->assertSame(100_000, $result->customerTotal);
        $this->assertSame(10_000, $result->platformFeeGross);
        $this->assertSame(5_000, $result->courierFee);
        $this->assertSame(1_000, $result->taxFee);
        $this->assertSame(3_000, $result->paymentFee);
        $this->assertSame(81_000, $result->sellerAmount);
    }
}
