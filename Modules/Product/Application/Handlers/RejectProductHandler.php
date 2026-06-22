<?php

declare(strict_types=1);

namespace Modules\Product\Application\Handlers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Product\Application\Commands\RejectProductCommand;
use Modules\Product\Domain\Repositories\ProductRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Models\Product as ProductModel;

final class RejectProductHandler
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
    ) {}

    public function handle(RejectProductCommand $command): ProductModel
    {
        $entity = $this->products->findById($command->id);

        if ($entity === null) {
            throw new ModelNotFoundException('Mahsulot topilmadi');
        }

        $rejected = $entity->reject($command->reason);
        $this->products->save($rejected);

        return ProductModel::findOrFail($command->id);
    }
}
