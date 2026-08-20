<?php

declare(strict_types=1);

namespace Modules\Courier\Application\Services;

use App\Shared\Exceptions\DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Courier\Infrastructure\Persistence\Models\CourierProfile;
use Modules\Order\Domain\Enums\OrderStatus;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;

final class TripBundlePlanner
{
    private const FALLBACK_DISTANCE_KM = 100.0;

    public function __construct(private readonly CourierRouteService $routes) {}

    /** @return Collection<int, array<string, mixed>> */
    public function availableRoutes(): Collection
    {
        return $this->eligibleQuery()->get()
            ->groupBy(fn (OrderModel $order): ?string => $this->routes->routeFor($order)['key'] ?? null)
            ->filter(fn (Collection $orders, $key): bool => $key !== null && $orders->isNotEmpty())
            ->map(function (Collection $orders): array {
                $summary = $this->routes->summarizeRoute($orders);

                return [
                    ...$summary,
                    'total_weight_kg' => round($orders->sum(fn (OrderModel $order): float => $this->orderWeightKg($order)), 3),
                    'oldest_order_at' => $orders->min('created_at')?->toISOString(),
                    'max_cargo_value' => (int) config('courier.trip.max_cargo_value', 50000000),
                ];
            })
            ->sortBy('oldest_order_at')
            ->values();
    }

    /**
     * @return array{route:array<string,string>,capacity_kg:float,max_orders:int,total_weight_kg:float,cargo_value:int,total_courier_fee:int,orders:Collection<int,array<string,mixed>>,pickup_sequences:array<string,int>,delivery_sequences:array<int,int>}
     */
    public function plan(int $courierId, string $routeKey, ?float $requestedCapacityKg = null, bool $lock = false): array
    {
        $profile = CourierProfile::query()->firstOrCreate(
            ['courier_id' => $courierId],
            [
                'vehicle_capacity_kg' => config('courier.trip.default_capacity_kg', 1000),
                'max_orders_per_trip' => config('courier.trip.default_max_orders', 10),
            ]
        );
        $profileCapacity = (float) $profile->vehicle_capacity_kg;
        $capacity = $requestedCapacityKg === null ? $profileCapacity : min($profileCapacity, $requestedCapacityKg);
        if ($capacity <= 0) {
            throw new DomainException('Transport yuk sig‘imini profilga kiriting.');
        }

        $query = $this->eligibleQuery()->orderBy('created_at')->orderBy('id');
        if ($lock) {
            $query->lockForUpdate();
        }

        $candidates = $query->get()
            ->filter(fn (OrderModel $order): bool => ($this->routes->routeFor($order)['key'] ?? null) === $routeKey)
            ->values();
        if ($candidates->isEmpty()) {
            throw new DomainException('Bu reysda bo‘sh buyurtma qolmadi. Ro‘yxatni yangilang.');
        }

        $anchor = $candidates->first();
        $route = $this->routes->routeFor($anchor);
        if ($route === null) {
            throw new DomainException('Reys yo‘nalishini aniqlab bo‘lmadi.');
        }

        $ranked = $candidates->map(function (OrderModel $order) use ($anchor): array {
            return [
                'order' => $order,
                'weight_kg' => $this->orderWeightKg($order),
                'pickup_distance_km' => $this->pickupDistanceKm($anchor, $order),
                'delivery_distance_km' => $this->deliveryDistanceKm($anchor, $order),
            ];
        })->sort(function (array $a, array $b): int {
            $distance = ($a['pickup_distance_km'] * 2 + $a['delivery_distance_km'])
                <=> ($b['pickup_distance_km'] * 2 + $b['delivery_distance_km']);

            return $distance !== 0 ? $distance : $a['order']->created_at <=> $b['order']->created_at;
        })->values();

        $selected = collect();
        $weight = 0.0;
        $cargoValue = 0;
        $courierFee = 0;
        $maxCargoValue = (int) config('courier.trip.max_cargo_value', 50000000);
        $maxOrders = (int) $profile->max_orders_per_trip;

        foreach ($ranked as $candidate) {
            $order = $candidate['order'];
            $orderValue = (int) $order->grand_total;
            if ($candidate['weight_kg'] <= 0 || $candidate['weight_kg'] > $capacity) {
                continue;
            }
            if ($candidate['pickup_distance_km'] > 60 || $candidate['delivery_distance_km'] > 80) {
                continue;
            }
            if ($selected->count() >= $maxOrders
                || $weight + $candidate['weight_kg'] > $capacity
                || $cargoValue + $orderValue > $maxCargoValue) {
                continue;
            }

            $selected->push($candidate);
            $weight += $candidate['weight_kg'];
            $cargoValue += $orderValue;
            $courierFee += (int) $order->courier_fee;
        }

        if ($selected->isEmpty()) {
            throw new DomainException('Sig‘im va 50 mln so‘mlik limitga mos buyurtma topilmadi.');
        }

        $pickupSequences = $this->pickupSequence($selected);
        $deliverySequences = $this->deliverySequence($selected);

        return [
            'route' => $route,
            'capacity_kg' => round($capacity, 3),
            'max_orders' => $maxOrders,
            'total_weight_kg' => round($weight, 3),
            'cargo_value' => $cargoValue,
            'total_courier_fee' => $courierFee,
            'orders' => $selected,
            'pickup_sequences' => $pickupSequences,
            'delivery_sequences' => $deliverySequences,
        ];
    }

    public function orderWeightKg(OrderModel $order): float
    {
        return round((float) $order->items->sum(function ($item): float {
            $unit = mb_strtolower((string) ($item->product?->unit ?: 'dona'));
            $factor = match ($unit) {
                'tonna', 'ton' => 1000.0,
                'kg', 'kilogramm' => 1.0,
                'litr', 'liter' => (float) ($item->product?->unit_weight_kg ?: 1),
                default => (float) ($item->product?->unit_weight_kg ?: 1),
            };

            return (int) $item->quantity * $factor;
        }), 3);
    }

    public function pickupKey(OrderModel $order): string
    {
        if ($order->seller_profile_id !== null) {
            return 'seller-'.$order->seller_profile_id;
        }

        // Legacy orders may not have seller_profile_id even though all their
        // products belong to one seller shop. Route cards already group those
        // products by that shop, so trip preview must use the same fallback.
        $productProfileIds = $order->items
            ->map(fn ($item): ?int => $item->product?->seller_profile_id)
            ->filter()
            ->unique()
            ->values();

        return $productProfileIds->count() === 1
            ? 'seller-'.$productProfileIds->first()
            : 'order-'.$order->id;
    }

    private function eligibleQuery(): Builder
    {
        return OrderModel::query()
            ->with(['items.product.sellerProfile', 'sellerProfile'])
            ->where('status', OrderStatus::READY_TO_DELIVER->value)
            ->whereNull('courier_id');
    }

    private function pickupDistanceKm(OrderModel $anchor, OrderModel $candidate): float
    {
        if ($anchor->id === $candidate->id) {
            return 0;
        }
        $a = $anchor->sellerProfile;
        $b = $candidate->sellerProfile;
        if ($a?->id !== null && $a->id === $b?->id) {
            return 0;
        }
        if ($this->sameText($a?->district, $b?->district)) {
            return 0;
        }
        if ($a?->pickup_latitude === null || $a?->pickup_longitude === null
            || $b?->pickup_latitude === null || $b?->pickup_longitude === null) {
            // Eski sellerlarda koordinata bo'lmasa, bir yo'nalish ichida neytral
            // masofa beriladi. Eng eski buyurtma baribir birinchi anchor bo'ladi.
            return 30;
        }

        return $this->distance(
            $a?->pickup_latitude,
            $a?->pickup_longitude,
            $b?->pickup_latitude,
            $b?->pickup_longitude,
        );
    }

    private function deliveryDistanceKm(OrderModel $anchor, OrderModel $candidate): float
    {
        if ($anchor->delivery_scope === 'city' && $candidate->delivery_scope === 'city'
            && $this->sameText(data_get($anchor->address, 'district'), data_get($candidate->address, 'district'))) {
            return 0;
        }
        if ($this->sameText(data_get($anchor->address, 'district'), data_get($candidate->address, 'district'))) {
            return 0;
        }

        return $this->distance(
            $anchor->delivery_latitude,
            $anchor->delivery_longitude,
            $candidate->delivery_latitude,
            $candidate->delivery_longitude,
        );
    }

    private function distance(mixed $lat1, mixed $lng1, mixed $lat2, mixed $lng2): float
    {
        if ($lat1 === null || $lng1 === null || $lat2 === null || $lng2 === null) {
            return self::FALLBACK_DISTANCE_KM;
        }

        $earth = 6371;
        $dLat = deg2rad((float) $lat2 - (float) $lat1);
        $dLng = deg2rad((float) $lng2 - (float) $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad((float) $lat1)) * cos(deg2rad((float) $lat2)) * sin($dLng / 2) ** 2;

        return $earth * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function sameText(mixed $a, mixed $b): bool
    {
        return $a !== null && $b !== null && mb_strtolower(trim((string) $a)) === mb_strtolower(trim((string) $b));
    }

    /** @param Collection<int, array<string,mixed>> $selected */
    private function deliverySequence(Collection $selected): array
    {
        $remaining = $selected->values();
        $sequence = [];
        $current = $remaining->shift();
        $position = 1;

        while ($current !== null) {
            $sequence[$current['order']->id] = $position++;
            if ($remaining->isEmpty()) {
                break;
            }
            $remaining = $remaining->sortBy(fn (array $entry): float => $this->distance(
                $current['order']->delivery_latitude,
                $current['order']->delivery_longitude,
                $entry['order']->delivery_latitude,
                $entry['order']->delivery_longitude,
            ))->values();
            $current = $remaining->shift();
        }

        return $sequence;
    }

    /** @param Collection<int, array<string,mixed>> $selected */
    private function pickupSequence(Collection $selected): array
    {
        $remaining = $selected
            ->unique(fn (array $entry): string => $this->pickupKey($entry['order']))
            ->values();
        $sequence = [];
        $current = $remaining->shift();
        $position = 1;

        while ($current !== null) {
            $sequence[$this->pickupKey($current['order'])] = $position++;
            if ($remaining->isEmpty()) {
                break;
            }
            $remaining = $remaining
                ->sortBy(fn (array $entry): float => $this->pickupDistanceKm(
                    $current['order'],
                    $entry['order'],
                ))
                ->values();
            $current = $remaining->shift();
        }

        return $sequence;
    }
}
