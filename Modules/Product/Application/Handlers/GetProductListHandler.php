<?php

declare(strict_types=1);

namespace Modules\Product\Application\Handlers;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Product\Application\Queries\GetProductListQuery;
use Modules\Product\Domain\Enums\ProductStatusEnum;
use Modules\Product\Infrastructure\Persistence\Models\Product as ProductModel;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

final class GetProductListHandler
{
    public function handle(GetProductListQuery $query): LengthAwarePaginator
    {
        return QueryBuilder::for(ProductModel::with('category'))
            ->allowedFilters(
                AllowedFilter::exact('category_id'),
                AllowedFilter::partial('name'),
                AllowedFilter::callback(
                    'min_price',
                    fn ($builder, $value) => $builder->where('price', '>=', (int) $value)
                ),
                AllowedFilter::callback(
                    'max_price',
                    fn ($builder, $value) => $builder->where('price', '<=', (int) $value)
                ),
            )
            ->allowedSorts('name', 'price', 'created_at')
            ->defaultSort('-created_at')
            ->where('status', ProductStatusEnum::Active->value)
            ->paginate($query->perPage);
    }
}
