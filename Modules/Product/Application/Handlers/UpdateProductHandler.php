<?php

declare(strict_types=1);

namespace Modules\Product\Application\Handlers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Product\Application\Commands\UpdateProductCommand;
use Modules\Product\Domain\Repositories\ProductRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Models\Product as ProductModel;

final class UpdateProductHandler
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
    ) {}

    public function handle(UpdateProductCommand $command): ProductModel
    {
        $entity = $this->products->findById($command->id);

        if ($entity === null) {
            throw new ModelNotFoundException('Mahsulot topilmadi');
        }

        $updated = $entity->modify(
            name:        $command->dto->name,
            slug:        $command->dto->slug,
            description: $command->dto->description,
            price:       $command->dto->price,
            stock:       $command->dto->stock,
            images:      $command->dto->images,
            categoryId:  $command->dto->categoryId,
        );

        $this->products->save($updated);

        return ProductModel::findOrFail($command->id);
    }
}
