<?php

declare(strict_types=1);

namespace Modules\Seller\Application\DTOs;

final readonly class UpdateSellerProfileDTO
{
    public function __construct(
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
    ) {}
}
