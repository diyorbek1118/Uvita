<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Handlers;

use Illuminate\Support\Facades\DB;
use Modules\Admin\Application\Queries\GetDashboardProductByIdQuery;
use Modules\Product\Infrastructure\Persistence\Models\Product as ProductModel;

final class GetDashboardProductByIdHandler
{
    public function handle(GetDashboardProductByIdQuery $query): ProductModel
    {
        $soldSub = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereColumn('order_items.product_id', 'products.id')
            ->whereIn('orders.status', GetDashboardProductsHandler::SOLD_STATUSES)
            ->selectRaw('COALESCE(SUM(order_items.quantity), 0)');

        $revenueSub = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereColumn('order_items.product_id', 'products.id')
            ->whereIn('orders.status', GetDashboardProductsHandler::SOLD_STATUSES)
            ->selectRaw('COALESCE(SUM(order_items.quantity * order_items.price), 0)');

        $builder = ProductModel::query()
            ->select('products.*')
            ->selectSub($soldSub, 'sold_count')
            ->selectSub($revenueSub, 'revenue')
            ->with(['manager', 'category'])
            ->where('products.id', $query->id);

        if ($query->managerId !== null) {
            $builder->where('manager_id', $query->managerId);
        }

        return $builder->firstOrFail();
    }
}
