<?php

declare(strict_types=1);

namespace Modules\Admin\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Buyurtma ro'yxati (dashboard) — yengil ko'rinish.
 */
class DashboardOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'status'      => $this->status->value,
            'customer'    => [
                'name'  => $this->whenLoaded('user', fn () => $this->user?->name),
                'phone' => $this->phone,
            ],
            'courier'     => $this->whenLoaded('courier', fn () => $this->courier ? [
                'id'   => $this->courier->id,
                'name' => $this->courier->name,
            ] : null),
            'items_count'    => $this->items_count ?? null,
            'total_price'    => $this->total_price,
            'service_fee'    => $this->service_fee,
            'grand_total'    => $this->grand_total,
            'delivery_time'  => $this->delivery_time,
            'created_at'     => $this->created_at?->toISOString(),
        ];
    }
}
