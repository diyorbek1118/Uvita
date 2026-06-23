<?php

declare(strict_types=1);

namespace Modules\Product\Application\Handlers;

use Modules\Product\Application\Queries\GetProductByIdQuery;
use Modules\Product\Domain\Enums\ProductStatusEnum;
use Modules\Product\Infrastructure\Persistence\Models\Product as ProductModel;

final class GetProductByIdHandler
{
    public function handle(GetProductByIdQuery $query): ProductModel
    {
        return ProductModel::with('category')
            ->where('status', ProductStatusEnum::Active->value)
            ->findOrFail($query->id);
    }
}
