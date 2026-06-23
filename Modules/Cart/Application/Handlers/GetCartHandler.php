<?php

declare(strict_types=1);

namespace Modules\Cart\Application\Handlers;

use Modules\Cart\Application\Queries\GetCartQuery;
use Modules\Cart\Infrastructure\Persistence\Models\CartModel;

final class GetCartHandler
{
    public function handle(GetCartQuery $query): ?CartModel
    {
        return CartModel::with('items.product')
            ->where('user_id', $query->userId)
            ->first();
    }
}
