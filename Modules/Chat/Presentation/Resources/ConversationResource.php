<?php

declare(strict_types=1);

namespace Modules\Chat\Presentation\Resources;

use App\Shared\Services\Upload\ImageUrlNormalizer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $userId   = $request->user()?->id;
        $other    = $this->buyer_id === $userId ? $this->seller : $this->buyer;
        $lastMsg  = $this->messages->last();

        return [
            'id'             => $this->id,
            'listing_id'     => $this->listing_id,
            'last_message_at'=> $this->last_message_at?->toISOString(),
            'other_user'     => [
                'id'     => $other->id,
                'name'   => $other->name,
                'avatar' => $other->avatar ? ImageUrlNormalizer::normalize($other->avatar) : null,
            ],
            'listing'        => $this->whenLoaded('listing', fn () => [
                'id'     => $this->listing->id,
                'title'  => $this->listing->title,
                'price'  => $this->listing->price,
                'image'  => ImageUrlNormalizer::normalizeArray($this->listing->images)[0] ?? null,
            ]),
            'last_message'   => $lastMsg?->body,
            'last_message_by_me' => $lastMsg ? $lastMsg->sender_id === $userId : false,
            'unread'         => $this->messages
                ->where('sender_id', '!=', $userId)
                ->whereNull('read_at')
                ->count(),
        ];
    }
}
