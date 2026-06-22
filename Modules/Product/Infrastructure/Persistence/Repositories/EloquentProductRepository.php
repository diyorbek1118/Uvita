<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Repositories;

use Modules\Product\Domain\Entities\Product;
use Modules\Product\Domain\Repositories\ProductRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Models\Product as ProductModel;

final class EloquentProductRepository implements ProductRepositoryInterface
{
    public function findById(int $id): ?Product
    {
        $model = ProductModel::find($id);

        return $model ? $this->toEntity($model) : null;
    }

    public function save(Product $product): Product
    {
        if ($product->id !== null) {
            $model = ProductModel::findOrFail($product->id);
            $model->update([
                'name'             => $product->name,
                'slug'             => $product->slug,
                'description'      => $product->description,
                'price'            => $product->price,
                'stock'            => $product->stock,
                'status'           => $product->status->value,
                'images'           => $product->images,
                'category_id'      => $product->categoryId,
                'rejection_reason' => $product->rejectionReason,
            ]);
        } else {
            $model = ProductModel::create([
                'name'        => $product->name,
                'slug'        => $product->slug,
                'description' => $product->description,
                'price'       => $product->price,
                'stock'       => $product->stock,
                'status'      => $product->status->value,
                'images'      => $product->images,
                'category_id' => $product->categoryId,
                'manager_id'  => $product->managerId,
            ]);
        }

        return $this->toEntity($model->fresh());
    }

    public function delete(int $id): void
    {
        ProductModel::findOrFail($id)->delete();
    }

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        return ProductModel::where('slug', $slug)
            ->when($excludeId !== null, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists();
    }

    private function toEntity(ProductModel $model): Product
    {
        return new Product(
            id:              $model->id,
            name:            $model->name,
            slug:            $model->slug,
            description:     $model->description,
            price:           $model->price,
            stock:           $model->stock,
            status:          $model->status,
            images:          $model->images ?? [],
            categoryId:      $model->category_id,
            managerId:       $model->manager_id,
            rejectionReason: $model->rejection_reason,
            createdAt:       $model->created_at?->toDateTimeImmutable(),
            updatedAt:       $model->updated_at?->toDateTimeImmutable(),
        );
    }
}
