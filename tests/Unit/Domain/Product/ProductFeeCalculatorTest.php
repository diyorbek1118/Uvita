<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Product;

use Modules\Product\Domain\Services\ProductFeeCalculator;
use Tests\TestCase;

final class ProductFeeCalculatorTest extends TestCase
{
    public function test_it_calculates_seller_net_with_configured_fee_breakdown(): void
    {
        config()->set('seller.fees', [
            'platform_percent' => 10,
            'courier_percent' => 5,
            'tax_percent' => 1,
            'payment_percent' => 3,
        ]);

        $result = (new ProductFeeCalculator())->calculate(100000);

        $this->assertSame(10000, $result->platformFee);
        $this->assertSame(5000, $result->courierFee);
        $this->assertSame(1000, $result->tax);
        $this->assertSame(3000, $result->paymentFee);
        $this->assertSame(81000, $result->sellerNet);
    }
}
