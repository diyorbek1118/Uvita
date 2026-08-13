<?php

declare(strict_types=1);

namespace Modules\Seller\Infrastructure\Persistence\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Seller\Domain\Repositories\SellerOrderReadRepositoryInterface;

final class EloquentSellerOrderReadRepository implements SellerOrderReadRepositoryInterface
{
    public function paginateForSeller(int $sellerId, int $perPage): LengthAwarePaginator
    {
        $netRate = 100 - (float) array_sum(config('seller.fees'));

        $orders = DB::table('orders')
            ->join('users', 'users.id', '=', 'orders.user_id')
            ->join('order_items', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->where('products.seller_id', $sellerId)
            ->whereIn('orders.status', ['paid', 'confirmed', 'ready_to_deliver', 'delivering', 'delivered'])
            ->select([
                'orders.id', 'orders.status', 'orders.created_at',
                'users.name as buyer_name',
            ])
            ->selectRaw('COUNT(DISTINCT order_items.product_id) as items_count')
            ->selectRaw('SUM(order_items.quantity) as units')
            ->selectRaw('SUM(order_items.quantity * order_items.price) as gross_amount')
            ->groupBy('orders.id', 'orders.status', 'orders.created_at', 'users.name')
            ->latest('orders.created_at')
            ->paginate($perPage);

        $orderIds = collect($orders->items())->pluck('id');
        $items = DB::table('order_items')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->where('products.seller_id', $sellerId)
            ->whereIn('order_items.order_id', $orderIds)
            ->select([
                'order_items.order_id', 'order_items.product_id', 'products.name as product_name',
                'order_items.quantity', 'order_items.price as unit_price',
            ])
            ->selectRaw('order_items.quantity * order_items.price as line_total')
            ->get()
            ->groupBy('order_id');

        $orders->setCollection($orders->getCollection()->map(function (object $order) use ($items, $netRate): array {
            $gross = (int) $order->gross_amount;

            return [
                'id' => (int) $order->id,
                'status' => (string) $order->status,
                'buyer_name' => (string) $order->buyer_name,
                'created_at' => (string) $order->created_at,
                'items_count' => (int) $order->items_count,
                'units' => (int) $order->units,
                'gross_amount' => $gross,
                'estimated_net_amount' => (int) round($gross * $netRate / 100),
                'items' => $items->get($order->id, collect())->map(fn (object $item): array => [
                    'product_id' => (int) $item->product_id,
                    'product_name' => (string) $item->product_name,
                    'quantity' => (int) $item->quantity,
                    'unit_price' => (int) $item->unit_price,
                    'line_total' => (int) $item->line_total,
                ])->values()->all(),
            ];
        }));

        return $orders;
    }
}
