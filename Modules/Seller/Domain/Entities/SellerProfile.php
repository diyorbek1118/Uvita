<?php

declare(strict_types=1);

namespace Modules\Seller\Domain\Entities;

final readonly class SellerProfile
{
    public function __construct(
        public int $sellerId,
        public string $businessName,
        public string $legalType,
        public string $tin,
        public string $phone,
        public string $region,
        public string $district,
        public string $address,
        public string $bankAccount,
        public string $bankMfo,
        public bool $termsAccepted,
        public bool $isVerified = false,
    ) {}
}
