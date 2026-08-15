<?php

declare(strict_types=1);

namespace Modules\Admin\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminCourierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $profile = $this->relationLoaded('courierProfile') ? $this->courierProfile : null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role->value,
            'is_active' => $this->is_active,
            'is_online' => (bool) ($profile?->is_online ?? false),
            'phone' => $profile?->phone,
            'vehicle_type' => $profile?->vehicle_type,
            'vehicle_number' => $profile?->vehicle_number,
            'last_seen_at' => $profile?->last_seen_at?->toISOString(),
            'delivering_count' => $this->when(
                $this->getAttribute('delivering_count') !== null,
                fn () => $this->getAttribute('delivering_count')
            ),
            'stats' => $this->when(
                $this->getAttribute('total_delivered') !== null,
                fn () => [
                    'total_delivered' => $this->getAttribute('total_delivered'),
                    'total_not_found' => $this->getAttribute('total_not_found'),
                    'total_active' => $this->getAttribute('total_active'),
                    'success_rate' => $this->getAttribute('success_rate'),
                ]
            ),
            'recent_deliveries' => $this->when(
                $this->getAttribute('recent_deliveries') !== null,
                fn () => collect($this->getAttribute('recent_deliveries'))->map(fn ($o) => [
                    'id' => $o->id,
                    'address' => $o->address,
                    'grand_total' => $o->grand_total,
                    'created_at' => $o->created_at?->toISOString(),
                ])->values()
            ),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
