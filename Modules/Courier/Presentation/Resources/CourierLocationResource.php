<?php

declare(strict_types=1);

namespace Modules\Courier\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CourierLocationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'courier_id' => $this->courier_id,
            'order_id' => $this->order_id,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'accuracy' => $this->accuracy,
            'recorded_at' => $this->recorded_at?->toISOString(),
        ];
    }
}
