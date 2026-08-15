<?php

declare(strict_types=1);

namespace Modules\Admin\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Seller\Presentation\Resources\SellerProfileResource;

class StaffResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role->value,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'shops' => SellerProfileResource::collection($this->whenLoaded('sellerProfiles')),
        ];
    }
}
