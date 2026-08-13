<?php

declare(strict_types=1);

namespace Modules\Deal\Presentation\Resources;

use App\Shared\Services\Upload\ImageUrlNormalizer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DealResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'listing_id'  => $this->listing_id,
            'quantity'    => $this->quantity,
            'unit'        => $this->unit,
            'total_price' => $this->total_price,
            'status'      => $this->status->value,
            'status_label'=> $this->status->label(),
            'created_at'  => $this->created_at?->toISOString(),
            'listing'     => $this->whenLoaded('listing', fn () => [
                'id'     => $this->listing->id,
                'title'  => $this->listing->title,
                'price'  => $this->listing->price,
                'images' => ImageUrlNormalizer::normalizeArray($this->listing->images),
            ]),
            'seller'      => $this->whenLoaded('seller', fn () => [
                'id'   => $this->seller->id,
                'name' => $this->seller->name,
            ]),
            'buyer'       => $this->whenLoaded('buyer', fn () => [
                'id'   => $this->buyer->id,
                'name' => $this->buyer->name,
            ]),
        ];
    }
}
