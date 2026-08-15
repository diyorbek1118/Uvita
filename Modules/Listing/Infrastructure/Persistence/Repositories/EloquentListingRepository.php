<?php

declare(strict_types=1);

namespace Modules\Listing\Infrastructure\Persistence\Repositories;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Str;
use Modules\Listing\Domain\Entities\Listing;
use Modules\Listing\Domain\Enums\ListingStatus;
use Modules\Listing\Domain\Repositories\ListingRepositoryInterface;
use Modules\Listing\Infrastructure\Persistence\Models\ListingModel;

final class EloquentListingRepository implements ListingRepositoryInterface
{
    public function findById(int $id): ?Listing
    {
        $model = ListingModel::find($id);

        return $model !== null ? $this->toDomain($model) : null;
    }

    public function save(Listing $listing): int
    {
        if ($listing->id === null) {
            $model = ListingModel::create([
                'seller_id' => $listing->sellerId,
                'category_id' => $listing->categoryId,
                'title' => $listing->title,
                'slug' => Str::slug($listing->title).'-'.Str::lower(Str::random(6)),
                'description' => $listing->description,
                'price' => $listing->price,
                'quantity' => $listing->quantity,
                'unit' => $listing->unit,
                'images' => $listing->images,
                'video' => $listing->video,
                'region' => $listing->region,
                'district' => $listing->district,
                'address' => $listing->address,
                'lat' => $listing->lat,
                'lng' => $listing->lng,
                'details' => $listing->details,
                'contacts' => $listing->contacts,
                'expires_at' => $listing->expiresAt,
                'status' => $listing->status->value,
                'rejection_reason' => $listing->rejectionReason,
            ]);

            return $model->id;
        }

        ListingModel::where('id', $listing->id)->update([
            'category_id' => $listing->categoryId,
            'title' => $listing->title,
            'description' => $listing->description,
            'price' => $listing->price,
            'quantity' => $listing->quantity,
            'unit' => $listing->unit,
            'images' => $listing->images,
            'video' => $listing->video,
            'region' => $listing->region,
            'district' => $listing->district,
            'address' => $listing->address,
            'lat' => $listing->lat,
            'lng' => $listing->lng,
            'details' => $listing->details,
            'contacts' => $listing->contacts,
            'expires_at' => $listing->expiresAt,
            'status' => $listing->status->value,
            'rejection_reason' => $listing->rejectionReason,
        ]);

        return $listing->id;
    }

    public function delete(int $id): void
    {
        ListingModel::where('id', $id)->delete();
    }

    public function paginateActive(array $filters = [], int $perPage = 20): Paginator
    {
        $query = ListingModel::with(['seller', 'category'])
            ->withAvg('sellerRatings as seller_rating', 'stars')
            ->withCount('sellerRatings as seller_rating_count')
            ->withAvg('listingRatings as listing_rating', 'stars')
            ->withCount('listingRatings as listing_rating_count')
            ->where('status', ListingStatus::ACTIVE->value)
            // Mudati tugagan e'lonlar bozorda ko'rinmaydi
            ->where(function ($q): void {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (! empty($filters['search'])) {
            $query->where('title', 'like', '%'.$filters['search'].'%');
        }

        if (! empty($filters['region'])) {
            $query->where('region', $filters['region']);
        }

        $sort = $filters['sort'] ?? 'newest';
        match ($sort) {
            'cheap' => $query->orderBy('price'),
            'expensive' => $query->orderByDesc('price'),
            default => $query->orderByDesc('id'),
        };

        return $query->paginate($perPage);
    }

    public function paginateBySeller(int $sellerId, int $perPage = 20): Paginator
    {
        return ListingModel::with(['seller', 'category'])
            ->where('seller_id', $sellerId)
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    private function toDomain(ListingModel $model): Listing
    {
        return new Listing(
            id: $model->id,
            sellerId: $model->seller_id,
            categoryId: $model->category_id,
            title: $model->title,
            description: $model->description,
            price: $model->price,
            quantity: (float) $model->quantity,
            unit: $model->unit,
            images: $model->images,
            video: $model->video,
            region: $model->region,
            district: $model->district,
            address: $model->address,
            lat: $model->lat !== null ? (float) $model->lat : null,
            lng: $model->lng !== null ? (float) $model->lng : null,
            details: $model->details,
            contacts: $model->contacts,
            expiresAt: $model->expires_at?->toDateTimeImmutable(),
            status: ListingStatus::from($model->status->value),
            rejectionReason: $model->rejection_reason,
        );
    }
}
