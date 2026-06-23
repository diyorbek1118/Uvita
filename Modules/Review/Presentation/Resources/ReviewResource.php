<?php

declare(strict_types=1);

namespace Modules\Review\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'order_id'   => $this->order_id,
            'product_id' => $this->product_id,
            'rating'     => $this->rating,
            'comment'    => $this->comment,
            'status'     => $this->status->value,
            'user'       => $this->whenLoaded('user', fn () => [
                'id'   => $this->user->id,
                'name' => $this->user->name,
            ]),
            'admin_note' => $this->when(
                $this->relationLoaded('user'),
                $this->admin_note
            ),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
