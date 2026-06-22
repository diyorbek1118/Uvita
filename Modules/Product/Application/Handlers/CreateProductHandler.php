<?php

declare(strict_types=1);

namespace Modules\Product\Application\Handlers;

use Modules\Product\Application\Commands\CreateProductCommand;
use Modules\Product\Domain\Entities\Product;
use Modules\Product\Domain\Repositories\ProductRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Models\Product as ProductModel;

final class CreateProductHandler
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
    ) {}

    public function handle(CreateProductCommand $command): ProductModel
    {
        $dto = $command->dto;

        $entity = Product::create(
            name:        $dto->name,
            slug:        $dto->slug,
            description: $dto->description,
            price:       $dto->price,
            stock:       $dto->stock,
            images:      $dto->images,
            categoryId:  $dto->categoryId,
            managerId:   $dto->managerId,
        );

        $saved = $this->products->save($entity);

        return ProductModel::findOrFail($saved->id);
    }
}
