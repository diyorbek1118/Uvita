<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Handlers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Admin\Application\Queries\GetAllProductsQuery;
use Modules\Product\Domain\Enums\ProductStatusEnum;
use Modules\Product\Infrastructure\Persistence\Models\Product as ProductModel;

final class GetAllProductsHandler
{
    public function handle(GetAllProductsQuery $query): LengthAwarePaginator
    {
        $builder = ProductModel::with('manager')->latest();

        if ($query->status !== null) {
            // "pending" — docs'da ishlatilgan alias, kod'da "inactive" deyiladi
            $statusValue = $query->status === 'pending' ? 'inactive' : $query->status;
            $statusEnum  = ProductStatusEnum::tryFrom($statusValue);

            if ($statusEnum !== null) {
                $builder->where('status', $statusEnum->value);
            }
        }

        return $builder->paginate(20);
    }
}
