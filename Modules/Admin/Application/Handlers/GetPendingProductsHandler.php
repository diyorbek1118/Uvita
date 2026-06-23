<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Handlers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Admin\Application\Queries\GetPendingProductsQuery;
use Modules\Product\Infrastructure\Persistence\Models\Product as ProductModel;

final class GetPendingProductsHandler
{
    public function handle(GetPendingProductsQuery $query): LengthAwarePaginator
    {
        return ProductModel::where('status', 'inactive')
            ->whereNotNull('manager_id')
            ->with('manager')
            ->latest()
            ->paginate(20);
    }
}
