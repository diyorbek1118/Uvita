<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Services;

use InvalidArgumentException;
use Modules\Product\Domain\ValueObjects\ProductPriceBreakdown;

final class ProductFeeCalculator
{
    public function calculate(int $price): ProductPriceBreakdown
    {
        if ($price < 0) {
            throw new InvalidArgumentException('Mahsulot narxi manfiy bo‘lishi mumkin emas');
        }

        $platform = (float) config('seller.fees.platform_percent', 10);
        $courier = (float) config('seller.fees.courier_percent', 5);
        $tax = (float) config('seller.fees.tax_percent', 1);
        $payment = (float) config('seller.fees.payment_percent', 3);

        $platformAmount = (int) round($price * $platform / 100);
        $courierAmount = (int) round($price * $courier / 100);
        $taxAmount = (int) round($price * $tax / 100);
        $paymentAmount = (int) round($price * $payment / 100);

        return new ProductPriceBreakdown(
            price: $price,
            platformFee: $platformAmount,
            courierFee: $courierAmount,
            tax: $taxAmount,
            paymentFee: $paymentAmount,
            sellerNet: $price - $platformAmount - $courierAmount - $taxAmount - $paymentAmount,
            platformPercent: $platform,
            courierPercent: $courier,
            taxPercent: $tax,
            paymentPercent: $payment,
        );
    }
}
