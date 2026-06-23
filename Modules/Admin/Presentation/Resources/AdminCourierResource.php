<?php

declare(strict_types=1);

namespace Modules\Admin\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminCourierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'name'      => $this->name,
            'email'     => $this->email,
            'role'      => $this->role->value,
            'is_active' => $this->is_active,
            'delivering_count' => $this->when(
                $this->getAttribute('delivering_count') !== null,
                fn () => $this->getAttribute('delivering_count')
            ),
            'stats' => $this->when(
                $this->getAttribute('total_delivered') !== null,
                fn () => [
                    'total_delivered' => $this->getAttribute('total_delivered'),
                    'total_not_found' => $this->getAttribute('total_not_found'),
                    'total_active'    => $this->getAttribute('total_active'),
                    'success_rate'    => $this->getAttribute('success_rate'),
                ]
            ),
            'recent_deliveries' => $this->when(
                $this->getAttribute('recent_deliveries') !== null,
                fn () => collect($this->getAttribute('recent_deliveries'))->map(fn ($o) => [
                    'id'          => $o->id,
                    'address'     => $o->address,
                    'grand_total' => $o->grand_total,
                    'created_at'  => $o->created_at?->toISOString(),
                ])->values()
            ),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
