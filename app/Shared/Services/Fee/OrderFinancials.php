<?php

declare(strict_types=1);

namespace App\Shared\Services\Fee;

/**
 * Bitta buyurtma bo'yicha moliyaviy taqsimot (so'mda).
 *
 * goods (total_price) — mijoz to'laydigan yakuniy mahsulotlar summasi.
 * customer_total      — mijoz to'laydigan jami = goods (ustama yo'q).
 * seller_amount       — barcha ichki ushlanmalardan keyingi sof seller tushumi.
 */
final readonly class OrderFinancials
{
    public function __construct(
        public int $sellerAmount,
        public int $platformFeeGross,
        public int $courierFee,
        public int $taxFee,
        public int $paymentFee,
        public int $platformFeeNet,
        public int $customerTotal,
    ) {}

    public function toArray(): array
    {
        return [
            'seller_amount'      => $this->sellerAmount,
            'platform_fee_gross' => $this->platformFeeGross,
            'courier_fee'        => $this->courierFee,
            'tax_fee'            => $this->taxFee,
            'payment_fee'        => $this->paymentFee,
            'platform_fee_net'   => $this->platformFeeNet,
            'customer_total'     => $this->customerTotal,
        ];
    }
}
