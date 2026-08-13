<?php

declare(strict_types=1);

namespace Modules\Order\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $reviewsByProduct = $this->relationLoaded('reviews')
            ? $this->reviews->keyBy('product_id')
            : collect();

        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'address' => $this->address,
            'delivery_location' => $this->delivery_latitude !== null && $this->delivery_longitude !== null ? [
                'latitude' => (float) $this->delivery_latitude,
                'longitude' => (float) $this->delivery_longitude,
            ] : null,
            'lat' => $this->lat !== null ? (float) $this->lat : null,
            'lng' => $this->lng !== null ? (float) $this->lng : null,
            'geo_level' => $this->geo_level,
            'phone' => $this->phone,
            'phone_secondary' => $this->phone_secondary,
            'delivery_time' => $this->delivery_time,
            'courier_note' => $this->courier_note,
            'total_price' => $this->total_price,   // mahsulotlar summasi
            'service_fee' => $this->service_fee,   // 15% xizmat haqi
            'grand_total' => $this->grand_total,   // jami to'lov (mahsulot + xizmat)
            // courier_fee mijozga KO'RSATILMAYDI
            'not_found_count' => $this->not_found_count,
            'payment_status' => $this->whenLoaded('latestPayment', fn () => $this->latestPayment?->status->value),
            'payment_url' => $this->payment_url ?? null,
            'paid_at' => $this->paid_at?->toISOString(),
            'confirmed_at' => $this->confirmed_at?->toISOString(),
            'ready_at' => $this->ready_at?->toISOString(),
            'delivering_at' => $this->delivering_at?->toISOString(),
            'delivered_at' => $this->delivered_at?->toISOString(),
            'delivery_issue_at' => $this->delivery_issue_at?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(function ($item) use ($reviewsByProduct) {
                $review = $reviewsByProduct->get($item->product_id);

                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product?->name,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'subtotal' => $item->price * $item->quantity,
                    'review' => $review ? [
                        'id' => $review->id,
                        'rating' => $review->rating,
                        'comment' => $review->comment,
                        'status' => $review->status->value,
                    ] : null,
                ];
            })
            ),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
