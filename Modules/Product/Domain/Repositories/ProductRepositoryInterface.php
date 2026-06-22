<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Repositories;

use Modules\Product\Domain\Entities\Product;

interface ProductRepositoryInterface
{
    public function findById(int $id): ?Product;
    public function save(Product $product): Product;
    public function delete(int $id): void;
    public function slugExists(string $slug, ?int $excludeId = null): bool;
}
