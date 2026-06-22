<?php

declare(strict_types=1);

namespace Modules\Category\Application\Handlers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Category\Application\Queries\GetCategoryListQuery;
use Modules\Category\Infrastructure\Persistence\Models\Category as CategoryModel;

final class GetCategoryListHandler
{
    public function handle(GetCategoryListQuery $query): LengthAwarePaginator
    {
        return CategoryModel::query()
            ->when(
                $query->parentId !== null,
                fn($q) => $q->where('parent_id', $query->parentId),
            )
            ->orderBy('name')
            ->paginate($query->perPage);
    }
}
