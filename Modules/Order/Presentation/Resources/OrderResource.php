<?php

declare(strict_types=1);

namespace Modules\Order\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'status'          => $this->status->value,
            'address'         => $this->address,
            'phone'           => $this->phone,
            'phone_secondary' => $this->phone_secondary,
            'delivery_time'   => $this->delivery_time,
            'courier_note'    => $this->courier_note,
            'delivery_price'  => $this->delivery_price,
            'total_price'     => $this->total_price,
            'grand_total'     => $this->grand_total,
            'not_found_count' => $this->not_found_count,
            'payment_url'     => $this->payment_url ?? null,
            'items'           => $this->whenLoaded('items', fn () =>
                $this->items->map(fn ($item) => [
                    'product_id'   => $item->product_id,
                    'product_name' => $item->product?->name,
                    'quantity'     => $item->quantity,
                    'price'        => $item->price,
                    'subtotal'     => $item->price * $item->quantity,
                ])
            ),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
