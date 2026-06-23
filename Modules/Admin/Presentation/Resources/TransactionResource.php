<?php

declare(strict_types=1);

namespace Modules\Admin\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'order_id'       => $this->order_id,
            'provider'       => $this->provider->value,
            'transaction_id' => $this->transaction_id,
            'amount'         => $this->amount,
            'status'         => $this->status->value,
            'created_at'     => $this->created_at?->toISOString(),
        ];
    }
}
