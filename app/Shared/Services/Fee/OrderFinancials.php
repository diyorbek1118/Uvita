<?php

declare(strict_types=1);

namespace App\Shared\Services\Fee;

/**
 * Bitta buyurtma bo'yicha moliyaviy taqsimot (so'mda).
 *
 * goods (total_price) — sotuvchiga to'lanadigan mahsulotlar summasi.
 * customer_total      — mijoz to'laydigan jami = goods + 15% ustama.
 * platform_fee_gross  — 15% ustama (yalpi).
 * courier_fee         — pog'onali kuryer haqi (goods'dan hisoblanadi).
 * platform_fee_net    — platformada qoladigan sof = gross - courier_fee.
 */
final readonly class OrderFinancials
{
    public function __construct(
        public int $sellerAmount,
        public int $platformFeeGross,
        public int $courierFee,
        public int $platformFeeNet,
        public int $customerTotal,
    ) {}

    public function toArray(): array
    {
        return [
            'seller_amount'      => $this->sellerAmount,
            'platform_fee_gross' => $this->platformFeeGross,
            'courier_fee'        => $this->courierFee,
            'platform_fee_net'   => $this->platformFeeNet,
            'customer_total'     => $this->customerTotal,
        ];
    }
}
