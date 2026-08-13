<?php

declare(strict_types=1);

namespace Modules\User\Presentation\Resources;

use App\Shared\Services\Upload\ImageUrlNormalizer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isOwner = $request->user()?->id === $this->id;

        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'surname'    => $this->surname,
            'phone'      => $isOwner ? $this->phone : null,
            'region'     => $this->region,
            'district'   => $this->district,
            'address'    => $isOwner ? $this->address : null,
            'lat'        => $isOwner ? ($this->lat !== null ? (float) $this->lat : null) : null,
            'lng'        => $isOwner ? ($this->lng !== null ? (float) $this->lng : null) : null,
            'avatar'      => $this->avatar ? ImageUrlNormalizer::normalize($this->avatar) : null,
            'has_password' => $isOwner ? ($this->password !== null && $this->password !== '') : null,
            'rating'      => round((float) ($this->ratings_avg_stars ?? 0), 1),
            'rating_count' => (int) ($this->ratings_count ?? 0),
            'deals_count'  => (int) ($this->deals_count ?? 0),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
