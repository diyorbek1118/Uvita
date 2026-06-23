<?php

declare(strict_types=1);

namespace Modules\Product\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'slug'            => $this->slug,
            'description'     => $this->description,
            'price'           => $this->price,
            'stock'           => $this->stock,
            'rating'          => $this->rating ?? 0.0,
            'reviews_count'   => $this->reviews_count ?? 0,
            'status'          => $this->status->value,
            'images'          => $this->images ?? [],
            'category'        => $this->whenLoaded('category', fn () => [
                'id'   => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ]),
            'managerId'       => $this->manager_id,
            'rejectionReason' => $this->rejection_reason,
            'createdAt'       => $this->created_at?->toISOString(),
        ];
    }
}
