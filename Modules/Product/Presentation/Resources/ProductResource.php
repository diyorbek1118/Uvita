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
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => $this->price,
            'stock' => $this->available_stock,
            'rating' => $this->rating ?? 0.0,
            'average_rating' => $this->rating ?? 0.0,
            'reviews_count' => $this->reviews_count ?? 0,
            'status' => $this->status->value,
            'images' => $this->images ?? [],
            'primary_image_index' => $this->primary_image_index ?? 0,
            'video_url' => $this->video_url,
            'origin' => [
                'region' => $this->origin_region,
                'farmer_name' => $this->farmer_name,
            ],
            'seller_shop' => $this->whenLoaded('sellerProfile', fn () => $this->sellerProfile === null ? null : [
                'id' => $this->sellerProfile->id,
                'business_name' => $this->sellerProfile->business_name,
                'region' => $this->sellerProfile->region,
                'district' => $this->sellerProfile->district,
                'is_verified' => (bool) $this->sellerProfile->is_verified,
            ]),
            'unit' => $this->unit,
            'minimum_order_quantity' => $this->minimum_order_quantity ?? 1,
            'category_id' => $this->category_id,
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ]),
            'managerId' => $this->manager_id,
            'rejectionReason' => $this->rejection_reason,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}
