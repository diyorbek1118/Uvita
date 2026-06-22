<?php

declare(strict_types=1);

namespace Modules\Category\Application\Handlers;

use Modules\Category\Application\Queries\GetCategoryByIdQuery;
use Modules\Category\Infrastructure\Persistence\Models\Category as CategoryModel;

final class GetCategoryByIdHandler
{
    public function handle(GetCategoryByIdQuery $query): CategoryModel
    {
        return CategoryModel::findOrFail($query->id);
    }
}
