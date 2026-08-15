<?php

declare(strict_types=1);

namespace Modules\Rating\Presentation\Resources;

use App\Shared\Services\Upload\ImageUrlNormalizer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RatingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'deal_id' => $this->deal_id,
            'listing_id' => $this->listing_id,
            'stars' => $this->stars,
            'comment' => $this->comment,
            'created_at' => $this->created_at?->toISOString(),
            'rater' => $this->whenLoaded('rater', fn () => [
                'id' => $this->rater->id,
                'name' => $this->rater->name,
                'avatar' => $this->rater->avatar ? ImageUrlNormalizer::normalize($this->rater->avatar) : null,
            ]),
            'listing' => $this->whenLoaded('listing', fn () => [
                'id' => $this->listing->id,
                'title' => $this->listing->title,
            ]),
            'deal' => $this->whenLoaded('deal', fn () => [
                'id' => $this->deal->id,
            ]),
        ];
    }
}
