<?php

declare(strict_types=1);

namespace Modules\Order\Application\Handlers;

use Modules\Courier\Application\Services\CourierRouteService;
use Modules\Order\Domain\Enums\OrderStatus;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;

final class GetAvailableCourierRoutesHandler
{
    public function __construct(private readonly CourierRouteService $routes) {}

    public function handle(): array
    {
        $orders = OrderModel::query()
            ->with(['items.product.sellerProfile'])
            ->where('status', OrderStatus::READY_TO_DELIVER->value)
            ->whereNull('courier_id')
            ->orderBy('ready_at')
            ->limit(200)
            ->get();

        return $orders
            ->filter(fn (OrderModel $order): bool => $this->routes->routeFor($order) !== null)
            ->groupBy(fn (OrderModel $order): string => $this->routes->routeFor($order)['key'])
            ->map(fn ($routeOrders): array => $this->routes->summarizeRoute($routeOrders))
            ->sortByDesc('orders_count')
            ->values()
            ->all();
    }
}
