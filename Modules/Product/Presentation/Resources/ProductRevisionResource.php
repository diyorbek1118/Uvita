<?php

declare(strict_types=1);

namespace Modules\Product\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ProductRevisionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'version' => $this->version,
            'status' => $this->status->value,
            'payload' => $this->payload,
            'pricing' => $this->fee_snapshot,
            'rejection_reason' => $this->rejection_reason,
            'seller' => $this->whenLoaded('seller', fn (): array => [
                'id' => $this->seller->id,
                'name' => $this->seller->name,
                'email' => $this->seller->email,
            ]),
            'submitted_at' => $this->created_at?->toISOString(),
            'reviewed_at' => $this->reviewed_at?->toISOString(),
        ];
    }
}
