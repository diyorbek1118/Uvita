<?php

declare(strict_types=1);

namespace Modules\Admin\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminUserDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'phone'        => $this->phone,
            'orders_count' => $this->orders_count ?? 0,
            'recent_orders' => collect($this->getAttribute('recent_orders') ?? [])->map(fn ($o) => [
                'id'          => $o->id,
                'status'      => $o->status->value,
                'grand_total' => $o->grand_total,
                'created_at'  => $o->created_at?->toISOString(),
            ])->values(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
