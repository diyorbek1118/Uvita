<?php

declare(strict_types=1);

namespace Modules\User\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'order_stats' => [
                'total' => (int) ($this->orders_total ?? 0),
                'delivered' => (int) ($this->orders_delivered ?? 0),
                'pending' => (int) ($this->orders_pending ?? 0),
                'delivering' => (int) ($this->orders_delivering ?? 0),
            ],
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
