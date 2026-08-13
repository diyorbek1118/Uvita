<?php

declare(strict_types=1);

namespace Modules\Seller\Application\Handlers;

use Modules\Seller\Application\DTOs\UpdateSellerProfileDTO;
use Modules\Seller\Infrastructure\Persistence\Models\SellerProfileModel;

final class UpdateSellerProfileHandler
{
    public function handle(int $sellerId, UpdateSellerProfileDTO $dto): SellerProfileModel
    {
        return SellerProfileModel::query()->updateOrCreate(
            ['seller_id' => $sellerId],
            [
                'business_name' => $dto->businessName,
                'legal_type' => $dto->legalType,
                'tin' => $dto->tin,
                'phone' => $dto->phone,
                'region' => $dto->region,
                'district' => $dto->district,
                'address' => $dto->address,
                'bank_account' => $dto->bankAccount,
                'bank_mfo' => $dto->bankMfo,
                'terms_accepted' => $dto->termsAccepted,
                'is_verified' => false,
                'verified_at' => null,
                'verified_by_id' => null,
            ],
        );
    }
}
