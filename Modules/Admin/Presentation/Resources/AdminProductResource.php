<?php

declare(strict_types=1);

namespace Modules\Admin\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'slug'             => $this->slug,
            'description'      => $this->description,
            'price'            => $this->price,
            'stock'            => $this->stock,
            'status'           => $this->status->value,
            'images'           => $this->images,
            'rating'           => $this->rating,
            'reviews_count'    => $this->reviews_count,
            'rejection_reason' => $this->rejection_reason,
            'manager'          => $this->whenLoaded('manager', fn () => [
                'id'   => $this->manager->id,
                'name' => $this->manager->name,
            ]),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
