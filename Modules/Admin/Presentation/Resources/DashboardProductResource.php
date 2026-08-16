<?php

declare(strict_types=1);

namespace Modules\Admin\Presentation\Resources;

use App\Shared\Services\Upload\ImageUrlNormalizer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => $this->price,
            'stock' => $this->stock,
            'reserved_stock' => $this->reserved_stock,
            'available_stock' => $this->available_stock,
            'sold_count' => (int) ($this->sold_count ?? 0),
            'revenue' => (int) ($this->revenue ?? 0),
            'status' => $this->status->value,
            'images' => ImageUrlNormalizer::normalizeArray($this->images),
            'rating' => $this->rating,
            'reviews_count' => $this->reviews_count,
            'rejection_reason' => $this->rejection_reason,
            'pending_revision' => $this->whenLoaded('latestPendingRevision', function (): ?array {
                if ($this->latestPendingRevision === null) {
                    return null;
                }

                $payload = $this->latestPendingRevision->payload;
                $payload['images'] = ImageUrlNormalizer::normalizeArray($payload['images'] ?? []);

                return [
                    'id' => $this->latestPendingRevision->id,
                    'version' => $this->latestPendingRevision->version,
                    'status' => $this->latestPendingRevision->status->value,
                    'payload' => $payload,
                    'pricing' => $this->latestPendingRevision->fee_snapshot,
                    'submitted_at' => $this->latestPendingRevision->created_at?->toISOString(),
                ];
            }),
            'category' => $this->whenLoaded('category', fn () => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ] : null),
            'manager' => $this->whenLoaded('manager', fn () => $this->manager ? [
                'id' => $this->manager->id,
                'name' => $this->manager->name,
            ] : null),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
