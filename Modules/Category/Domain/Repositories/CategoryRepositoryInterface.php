<?php

declare(strict_types=1);

namespace Modules\Category\Domain\Repositories;

use Modules\Category\Domain\Entities\Category;

interface CategoryRepositoryInterface
{
    public function findById(int $id): ?Category;

    public function save(Category $category): Category;

    public function delete(int $id): void;

    public function slugExists(string $slug, ?int $excludeId = null): bool;
}
