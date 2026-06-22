<?php

declare(strict_types=1);

namespace Modules\Category\Infrastructure\Persistence\Repositories;

use Modules\Category\Domain\Entities\Category as CategoryEntity;
use Modules\Category\Domain\Repositories\CategoryRepositoryInterface;
use Modules\Category\Infrastructure\Persistence\Models\Category as CategoryModel;

final class EloquentCategoryRepository implements CategoryRepositoryInterface
{
    public function findById(int $id): ?CategoryEntity
    {
        $model = CategoryModel::find($id);

        return $model ? $this->toEntity($model) : null;
    }

    public function save(CategoryEntity $category): CategoryEntity
    {
        if ($category->id !== null) {
            $model = CategoryModel::findOrFail($category->id);
        } else {
            $model = new CategoryModel();
        }

        $model->name      = $category->name;
        $model->slug      = $category->slug;
        $model->image     = $category->image;
        $model->parent_id = $category->parentId;
        $model->is_active = $category->isActive;
        $model->save();

        return $this->toEntity($model);
    }

    public function delete(int $id): void
    {
        CategoryModel::where('id', $id)->delete();
    }

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        return CategoryModel::query()
            ->where('slug', $slug)
            ->when($excludeId !== null, fn($q) => $q->where('id', '!=', $excludeId))
            ->exists();
    }

    private function toEntity(CategoryModel $model): CategoryEntity
    {
        return new CategoryEntity(
            id:        $model->id,
            name:      $model->name,
            slug:      $model->slug,
            image:     $model->image,
            parentId:  $model->parent_id,
            isActive:  $model->is_active,
            createdAt: $model->created_at?->toDateTimeImmutable(),
            updatedAt: $model->updated_at?->toDateTimeImmutable(),
        );
    }
}
