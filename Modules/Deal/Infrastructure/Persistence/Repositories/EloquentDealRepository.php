<?php

declare(strict_types=1);

namespace Modules\Deal\Infrastructure\Persistence\Repositories;

use Illuminate\Contracts\Pagination\Paginator;
use Modules\Deal\Domain\Entities\Deal;
use Modules\Deal\Domain\Enums\DealStatus;
use Modules\Deal\Domain\Repositories\DealRepositoryInterface;
use Modules\Deal\Infrastructure\Persistence\Models\DealModel;

final class EloquentDealRepository implements DealRepositoryInterface
{
    public function findById(int $id): ?Deal
    {
        $model = DealModel::find($id);

        return $model !== null ? $this->toDomain($model) : null;
    }

    public function save(Deal $deal): int
    {
        if ($deal->id === null) {
            $model = DealModel::create([
                'listing_id' => $deal->listingId,
                'seller_id' => $deal->sellerId,
                'buyer_id' => $deal->buyerId,
                'quantity' => $deal->quantity,
                'unit' => $deal->unit,
                'total_price' => $deal->totalPrice,
                'status' => $deal->status->value,
            ]);

            return $model->id;
        }

        DealModel::where('id', $deal->id)->update([
            'quantity' => $deal->quantity,
            'unit' => $deal->unit,
            'total_price' => $deal->totalPrice,
            'status' => $deal->status->value,
        ]);

        return $deal->id;
    }

    public function paginateIncoming(int $sellerId, int $perPage = 20): Paginator
    {
        return DealModel::with(['listing.seller', 'listing.category', 'buyer'])
            ->where('seller_id', $sellerId)
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function paginateOutgoing(int $buyerId, int $perPage = 20): Paginator
    {
        return DealModel::with(['listing.seller', 'listing.category', 'seller'])
            ->where('buyer_id', $buyerId)
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    private function toDomain(DealModel $model): Deal
    {
        return new Deal(
            id: $model->id,
            listingId: $model->listing_id,
            sellerId: $model->seller_id,
            buyerId: $model->buyer_id,
            quantity: (float) $model->quantity,
            unit: $model->unit,
            totalPrice: $model->total_price,
            status: DealStatus::from($model->status->value),
        );
    }
}
