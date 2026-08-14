<?php

declare(strict_types=1);

namespace Modules\Product\Domain\ValueObjects;

final readonly class ProductPriceBreakdown
{
    public function __construct(
        public int $price,
        public int $platformFee,
        public int $courierFee,
        public int $tax,
        public int $paymentFee,
        public int $sellerNet,
        public float $platformPercent,
        public float $courierPercent,
        public float $taxPercent,
        public float $paymentPercent,
    ) {}

    public function toArray(): array
    {
        return [
            'price' => $this->price,
            'deductions' => [
                'platform_fee' => ['percent' => $this->platformPercent, 'amount' => $this->platformFee],
                'courier_fee' => ['percent' => $this->courierPercent, 'amount' => $this->courierFee],
            ],
            'seller_net' => $this->sellerNet,
        ];
    }
}
