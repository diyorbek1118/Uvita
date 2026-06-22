<?php

declare(strict_types=1);

namespace Modules\Product\Application\Handlers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Product\Application\Commands\ApproveProductCommand;
use Modules\Product\Domain\Repositories\ProductRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Models\Product as ProductModel;

final class ApproveProductHandler
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
    ) {}

    public function handle(ApproveProductCommand $command): ProductModel
    {
        $entity = $this->products->findById($command->id);

        if ($entity === null) {
            throw new ModelNotFoundException('Mahsulot topilmadi');
        }

        $approved = $entity->approve();
        $this->products->save($approved);

        return ProductModel::findOrFail($command->id);
    }
}
