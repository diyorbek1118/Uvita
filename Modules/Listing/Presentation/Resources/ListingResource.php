<?php

declare(strict_types=1);

namespace Modules\Listing\Presentation\Resources;

use App\Shared\Services\Upload\ImageUrlNormalizer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'title' => $this->title,
            'description' => $this->description,
            'price' => $this->price,
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'images' => ImageUrlNormalizer::normalizeArray($this->images),
            'video' => $this->video ? ImageUrlNormalizer::normalize($this->video) : null,
            'region' => $this->region,
            'district' => $this->district,
            'address' => $this->address,
            'lat' => $this->lat !== null ? (float) $this->lat : null,
            'lng' => $this->lng !== null ? (float) $this->lng : null,
            'details' => $this->details,
            'contacts' => $this->contacts,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'expires_at' => $this->expires_at?->toISOString(),
            'views' => $this->views ?? 0,
            'rejection_reason' => $this->rejection_reason,
            'created_at' => $this->created_at?->toISOString(),
            'seller' => $this->whenLoaded('seller', fn () => [
                'id' => $this->seller->id,
                'name' => $this->seller->name,
                // C2C bozor modelida aloqa uchun telefon ochiq ko'rsatiladi
                'phone' => $this->seller->phone,
                'region' => $this->seller->region,
                'avatar' => $this->seller->avatar ? ImageUrlNormalizer::normalize($this->seller->avatar) : null,
            ]),
            // Mahsulot bo'yicha reyting (xaridorlar bahosi)
            'rating' => round((float) ($this->listing_rating ?? $this->seller_rating ?? 0), 1),
            'rating_count' => (int) ($this->listing_rating_count ?? $this->seller_rating_count ?? 0),
            'seller_rating' => round((float) ($this->seller_rating ?? 0), 1),
            'seller_rating_count' => (int) ($this->seller_rating_count ?? 0),
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ]),
        ];
    }
}
