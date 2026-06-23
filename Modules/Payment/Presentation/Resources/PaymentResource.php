<?php

declare(strict_types=1);

namespace Modules\Payment\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'order_id'    => $this->order_id,
            'provider'    => $this->provider->value,
            'status'      => $this->status->value,
            'amount'      => $this->amount,
            'created_at'  => $this->created_at?->toISOString(),
        ];
    }
}
