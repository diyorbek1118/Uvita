<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Handlers;

use Illuminate\Support\Facades\DB;
use Modules\Admin\Application\Queries\GetTopProductsQuery;

/**
 * Eng ko'p sotilgan mahsulotlar (miqdor bo'yicha) + tushum.
 */
final class GetTopProductsHandler
{
    public function handle(GetTopProductsQuery $query): array
    {
        $limit = max(1, min(50, $query->limit));

        return DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->join('products as p', 'p.id', '=', 'oi.product_id')
            ->whereIn('o.status', GetDashboardProductsHandler::SOLD_STATUSES)
            ->groupBy('p.id', 'p.name')
            ->selectRaw('p.id, p.name, SUM(oi.quantity) as units_sold, SUM(oi.quantity * oi.price) as revenue')
            ->orderByDesc('units_sold')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'id'         => (int) $row->id,
                'name'       => $row->name,
                'units_sold' => (int) $row->units_sold,
                'revenue'    => (int) $row->revenue,
            ])
            ->all();
    }
}
