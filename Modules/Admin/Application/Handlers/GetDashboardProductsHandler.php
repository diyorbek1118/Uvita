<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Handlers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Application\Queries\GetDashboardProductsQuery;
use Modules\Product\Domain\Enums\ProductStatusEnum;
use Modules\Product\Infrastructure\Persistence\Models\Product as ProductModel;

final class GetDashboardProductsHandler
{
    /**
     * Sotilgan hisoblanadigan buyurtma statuslari (stock faqat paid'da kamayadi;
     * pending va cancelled sotuvga kirmaydi).
     */
    public const SOLD_STATUSES = [
        'paid', 'confirmed', 'ready_to_deliver',
        'delivering', 'delivered', 'delivery_issue',
    ];

    public function handle(GetDashboardProductsQuery $query): LengthAwarePaginator
    {
        $soldSub = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereColumn('order_items.product_id', 'products.id')
            ->whereIn('orders.status', self::SOLD_STATUSES)
            ->selectRaw('COALESCE(SUM(order_items.quantity), 0)');

        $revenueSub = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereColumn('order_items.product_id', 'products.id')
            ->whereIn('orders.status', self::SOLD_STATUSES)
            ->selectRaw('COALESCE(SUM(order_items.quantity * order_items.price), 0)');

        $builder = ProductModel::query()
            ->select('products.*')
            ->selectSub($soldSub, 'sold_count')
            ->selectSub($revenueSub, 'revenue')
            ->with(['manager', 'category'])
            ->latest();

        if ($query->managerId !== null) {
            $builder->where('manager_id', $query->managerId);
        }

        if ($query->status !== null) {
            // "pending" — docs alias, kod'da "inactive"
            $statusValue = $query->status === 'pending' ? 'inactive' : $query->status;
            $statusEnum  = ProductStatusEnum::tryFrom($statusValue);
            if ($statusEnum !== null) {
                $builder->where('status', $statusEnum->value);
            }
        }

        if ($query->categoryId !== null) {
            $builder->where('category_id', $query->categoryId);
        }

        if ($query->search !== null && $query->search !== '') {
            $builder->where('name', 'like', '%' . $query->search . '%');
        }

        if ($query->maxStock !== null) {
            $builder->where('stock', '<=', $query->maxStock)->reorder('stock', 'asc');
        }

        return $builder->paginate($query->perPage);
    }
}
