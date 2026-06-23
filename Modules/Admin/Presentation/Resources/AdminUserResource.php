<?php

declare(strict_types=1);

namespace Modules\Admin\Presentation\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'phone'         => $this->phone,
            'orders_count'  => $this->orders_count ?? 0,
            'last_order_at' => $this->orders_max_created_at
                ? Carbon::parse($this->orders_max_created_at)->toISOString()
                : null,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
