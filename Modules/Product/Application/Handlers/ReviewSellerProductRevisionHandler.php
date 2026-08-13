<?php

declare(strict_types=1);

namespace Modules\Product\Application\Handlers;

use Illuminate\Support\Facades\DB;
use Modules\Product\Domain\Enums\ProductRevisionStatusEnum;
use Modules\Product\Domain\Enums\ProductStatusEnum;
use Modules\Product\Infrastructure\Persistence\Models\ProductRevision;

final class ReviewSellerProductRevisionHandler
{
    public function approve(int $revisionId, int $reviewerId): ProductRevision
    {
        return DB::transaction(function () use ($revisionId, $reviewerId): ProductRevision {
            $revision = ProductRevision::query()->lockForUpdate()->findOrFail($revisionId);
            abort_if($revision->status !== ProductRevisionStatusEnum::PENDING, 422, 'Bu tahrir allaqachon ko‘rib chiqilgan');

            $product = $revision->product()->lockForUpdate()->firstOrFail();
            $payload = $revision->payload;
            $product->update([
                'name' => $payload['name'],
                'description' => $payload['description'],
                'price' => $payload['price'],
                'stock' => $payload['stock'],
                'category_id' => $payload['category_id'],
                'images' => $payload['images'],
                'primary_image_index' => $payload['primary_image_index'],
                'video_url' => $payload['video_url'],
                'origin_region' => $payload['origin_region'],
                'farmer_name' => $payload['farmer_name'],
                'unit' => $payload['unit'],
                'minimum_order_quantity' => $payload['minimum_order_quantity'],
                'fee_snapshot' => $revision->fee_snapshot,
                'approved_version' => $revision->version,
                'status' => ProductStatusEnum::Active,
                'rejection_reason' => null,
            ]);

            $revision->update([
                'status' => ProductRevisionStatusEnum::APPROVED,
                'reviewed_by_id' => $reviewerId,
                'reviewed_at' => now(),
            ]);

            ProductRevision::query()
                ->where('product_id', $product->id)
                ->where('id', '!=', $revision->id)
                ->where('status', ProductRevisionStatusEnum::PENDING)
                ->update([
                    'status' => ProductRevisionStatusEnum::REJECTED,
                    'rejection_reason' => 'Yangi versiya tasdiqlangani sabab yopildi',
                    'reviewed_by_id' => $reviewerId,
                    'reviewed_at' => now(),
                ]);

            return $revision->fresh(['product', 'seller']);
        });
    }

    public function reject(int $revisionId, int $reviewerId, string $reason): ProductRevision
    {
        $revision = ProductRevision::query()->findOrFail($revisionId);
        abort_if($revision->status !== ProductRevisionStatusEnum::PENDING, 422, 'Bu tahrir allaqachon ko‘rib chiqilgan');

        $revision->update([
            'status' => ProductRevisionStatusEnum::REJECTED,
            'rejection_reason' => $reason,
            'reviewed_by_id' => $reviewerId,
            'reviewed_at' => now(),
        ]);

        return $revision->fresh(['product', 'seller']);
    }
}
