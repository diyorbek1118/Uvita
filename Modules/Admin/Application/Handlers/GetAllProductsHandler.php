<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Handlers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Admin\Application\Queries\GetAllProductsQuery;
use Modules\Product\Infrastructure\Persistence\Models\Product as ProductModel;

final class GetAllProductsHandler
{
    public function handle(GetAllProductsQuery $query): LengthAwarePaginator
    {
        $builder = ProductModel::with('manager')->latest();

        if ($query->status !== null) {
            $builder->where('status', $query->status);
        }

        return $builder->paginate(20);
    }
}
