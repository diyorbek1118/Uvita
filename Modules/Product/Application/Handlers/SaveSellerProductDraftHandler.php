<?php

declare(strict_types=1);

namespace Modules\Product\Application\Handlers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Product\Application\DTOs\SellerProductDraftDTO;
use Modules\Product\Domain\Enums\ProductRevisionStatusEnum;
use Modules\Product\Domain\Enums\ProductStatusEnum;
use Modules\Product\Domain\Services\ProductFeeCalculator;
use Modules\Product\Infrastructure\Persistence\Models\Product;
use Modules\Product\Infrastructure\Persistence\Models\ProductRevision;

final class SaveSellerProductDraftHandler
{
    public function __construct(private readonly ProductFeeCalculator $fees) {}

    public function handle(SellerProductDraftDTO $dto, int $sellerId, ?int $productId = null): ProductRevision
    {
        return DB::transaction(function () use ($dto, $sellerId, $productId): ProductRevision {
            $product = $productId === null
                ? Product::create([
                    'name' => $dto->name,
                    'slug' => $this->uniqueSlug($dto->slug),
                    'description' => $dto->description,
                    'price' => $dto->price,
                    'stock' => $dto->stock,
                    'status' => ProductStatusEnum::Inactive,
                    'images' => [],
                    'category_id' => $dto->categoryId,
                    'seller_id' => $sellerId,
                ])
                : Product::query()->where('seller_id', $sellerId)->lockForUpdate()->findOrFail($productId);

            $version = ((int) $product->revisions()->max('version')) + 1;
            $snapshot = $this->fees->calculate($dto->price)->toArray();

            return ProductRevision::create([
                'product_id' => $product->id,
                'seller_id' => $sellerId,
                'version' => $version,
                'payload' => $dto->toPayload(),
                'fee_snapshot' => $snapshot,
                'status' => ProductRevisionStatusEnum::PENDING,
            ])->load('product');
        });
    }

    private function uniqueSlug(string $slug): string
    {
        $base = Str::slug($slug);
        $candidate = $base;
        $suffix = 2;

        while (Product::query()->where('slug', $candidate)->exists()) {
            $candidate = $base.'-'.$suffix++;
        }

        return $candidate;
    }
}
