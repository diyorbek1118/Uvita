<?php

declare(strict_types=1);

namespace Modules\Courier\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CourierSupportTicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'courier_id' => $this->courier_id,
            'order_id' => $this->order_id,
            'category' => $this->category,
            'message' => $this->message,
            'status' => $this->status->value,
            'admin_reply' => $this->admin_reply,
            'resolved_at' => $this->resolved_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
