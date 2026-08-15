<?php

declare(strict_types=1);

namespace Modules\Courier\Application\Services;

use Illuminate\Support\Collection;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;

final class CourierRouteService
{
    /** @return array{key:string, origin_region:string, destination_region:string}|null */
    public function routeFor(OrderModel $order): ?array
    {
        $origins = $order->items
            ->map(fn ($item): ?string => $this->itemOrigin($item))
            ->filter()
            ->map(fn (string $region): string => trim($region))
            ->unique(fn (string $region): string => mb_strtolower($region))
            ->values();

        $destination = trim((string) data_get($order->address, 'region', ''));
        if ($origins->count() !== 1 || $destination === '') {
            return null;
        }

        $origin = (string) $origins->first();

        return [
            'key' => mb_strtolower($origin).'|'.mb_strtolower($destination),
            'origin_region' => $origin,
            'destination_region' => $destination,
        ];
    }

    /** @param Collection<int, OrderModel> $orders */
    public function summarizeRoute(Collection $orders): array
    {
        $firstRoute = $this->routeFor($orders->first());
        $unitTotals = [];
        $pickupPoints = [];
        $productPositions = 0;

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $unit = (string) ($item->product?->unit ?: 'dona');
                $unitTotals[$unit] = ($unitTotals[$unit] ?? 0) + (int) $item->quantity;
                $productPositions++;

                $profile = $item->product?->sellerProfile;
                $pickupKey = $profile?->id !== null
                    ? 'seller-'.$profile->id
                    : 'origin-'.mb_strtolower((string) $this->itemOrigin($item));
                $pickupPoints[$pickupKey] = true;
            }
        }

        ksort($unitTotals);

        return [
            ...$firstRoute,
            'orders_count' => $orders->count(),
            'pickup_points_count' => count($pickupPoints),
            'product_positions_count' => $productPositions,
            'load_by_unit' => $unitTotals,
            'total_courier_fee' => (int) $orders->sum('courier_fee'),
            'total_grand_total' => (int) $orders->sum('grand_total'),
            'orders' => $orders->map(fn (OrderModel $order): array => $this->availableOrderSummary($order))->values(),
        ];
    }

    public function sameRoute(Collection $orders): bool
    {
        $routes = $orders->map(fn (OrderModel $order): ?string => $this->routeFor($order)['key'] ?? null);

        return ! $routes->contains(null) && $routes->unique()->count() === 1;
    }

    private function availableOrderSummary(OrderModel $order): array
    {
        $address = $order->address ?? [];

        return [
            'id' => $order->id,
            'destination' => [
                'region' => $address['region'] ?? null,
                'district' => $address['district'] ?? null,
            ],
            'delivery_time' => $order->delivery_time,
            'courier_fee' => $order->courier_fee,
            'grand_total' => $order->grand_total,
            'items' => $order->items->map(fn ($item): array => [
                'product_name' => $item->product?->name,
                'quantity' => $item->quantity,
                'unit' => $item->product?->unit ?: 'dona',
                'pickup_district' => $item->product?->sellerProfile?->district,
            ])->values(),
        ];
    }

    private function itemOrigin(mixed $item): ?string
    {
        return $item->product?->sellerProfile?->region
            ?: $item->product?->origin_region;
    }
}
