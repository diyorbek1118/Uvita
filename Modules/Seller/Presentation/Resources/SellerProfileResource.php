<?php

declare(strict_types=1);

namespace Modules\Seller\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SellerProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'seller_id' => $this->seller_id,
            'business_name' => $this->business_name,
            'legal_type' => $this->legal_type,
            'tin' => $this->tin,
            'phone' => $this->phone,
            'region' => $this->region,
            'district' => $this->district,
            'address' => $this->address,
            'bank_account' => $this->bank_account,
            'bank_mfo' => $this->bank_mfo,
            'is_active' => $this->is_active,
            'is_verified' => $this->is_verified,
            'verified_at' => $this->verified_at?->toISOString(),
        ];
    }
}
