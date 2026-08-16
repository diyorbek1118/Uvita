<?php

declare(strict_types=1);

namespace Modules\Courier\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourierProfileResource extends JsonResource
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
            'phone' => $profile?->phone,
            'vehicle_type' => $profile?->vehicle_type,
            'vehicle_number' => $profile?->vehicle_number,
            'vehicle_capacity_kg' => (float) ($profile?->vehicle_capacity_kg ?? config('courier.trip.default_capacity_kg', 1000)),
            'max_orders_per_trip' => (int) ($profile?->max_orders_per_trip ?? config('courier.trip.default_max_orders', 10)),
            'photo' => $profile?->photo,
            'is_online' => (bool) ($profile?->is_online ?? false),
            'shift_started_at' => $profile?->shift_started_at?->toISOString(),
            'shift_ended_at' => $profile?->shift_ended_at?->toISOString(),
            'last_seen_at' => $profile?->last_seen_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
