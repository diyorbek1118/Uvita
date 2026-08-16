<?php

declare(strict_types=1);

namespace Modules\Courier\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Courier\Domain\Enums\CourierTripStatus;

final class CourierTripResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $revealed = $this->status !== CourierTripStatus::PICKING_UP;
        $completed = $this->status === CourierTripStatus::COMPLETED;

        return [
            'id' => $this->id,
            'route' => [
                'origin_region' => $this->origin_region,
                'destination_region' => $this->destination_region,
            ],
            'status' => $this->status->value,
            'capacity_kg' => (float) $this->capacity_kg,
            'total_weight_kg' => (float) $this->total_weight_kg,
            'orders_count' => $this->orders_count,
            'cargo_value' => $this->cargo_value,
            'total_courier_fee' => $this->total_courier_fee,
            'cash_collected' => $this->cash_collected,
            'platform_cash_due' => $completed
                ? max(0, (int) $this->cash_collected - (int) $this->total_courier_fee)
                : null,
            'customer_addresses_revealed' => $revealed,
            'pickup_points' => $this->whenLoaded('tripOrders', fn () => $this->tripOrders
                ->groupBy('pickup_key')
                ->map(function ($loads): array {
                    $first = $loads->sortBy('pickup_sequence')->first();
                    $profile = $first->order->sellerProfile
                        ?: $first->order->items->first()?->product?->sellerProfile;

                    return [
                        'key' => $first->pickup_key,
                        'sequence' => $first->pickup_sequence,
                        'business_name' => $profile?->business_name,
                        'phone' => $profile?->phone,
                        'region' => $profile?->region ?: $this->origin_region,
                        'district' => $profile?->district,
                        'address' => $profile?->address,
                        'location' => $profile?->pickup_latitude !== null ? [
                            'latitude' => (float) $profile->pickup_latitude,
                            'longitude' => (float) $profile->pickup_longitude,
                        ] : null,
                        'orders_count' => $loads->count(),
                        'weight_kg' => round((float) $loads->sum('weight_kg'), 3),
                        'picked_up' => $loads->every(fn ($load): bool => $load->picked_up_at !== null),
                        'picked_up_at' => $loads->max('picked_up_at')?->toISOString(),
                    ];
                })->sortBy('sequence')->values()),
            'deliveries' => $this->when($revealed && $this->relationLoaded('tripOrders'), fn () => $this->tripOrders
                ->sortBy('delivery_sequence')
                ->map(function ($load): array {
                    $order = $load->order;
                    $cityDelivery = $order->delivery_scope === 'city';
                    $visibleAddress = $cityDelivery ? $order->address : [
                        'region' => data_get($order->address, 'region'),
                        'district' => data_get($order->address, 'district'),
                        'delivery_point' => 'Tuman markazi',
                    ];

                    return [
                        'order_id' => $order->id,
                        'sequence' => $load->delivery_sequence,
                        'phone' => $load->delivered_at === null ? $order->phone : null,
                        'address' => $load->delivered_at === null ? $visibleAddress : [
                            'region' => data_get($order->address, 'region'),
                            'district' => data_get($order->address, 'district'),
                        ],
                        'delivery_scope' => $order->delivery_scope,
                        'location' => $load->delivered_at === null && $cityDelivery && $order->delivery_latitude !== null ? [
                            'latitude' => (float) $order->delivery_latitude,
                            'longitude' => (float) $order->delivery_longitude,
                        ] : null,
                        'cash_due' => $order->grand_total,
                        'courier_fee' => $order->courier_fee,
                        'delivered' => $load->delivered_at !== null,
                        'delivered_at' => $load->delivered_at?->toISOString(),
                        'items' => $order->items->map(fn ($item): array => [
                            'name' => $item->product?->name,
                            'quantity' => $item->quantity,
                            'unit' => $item->product?->unit ?: 'dona',
                        ])->values(),
                    ];
                })->values()),
            'accepted_at' => $this->accepted_at?->toISOString(),
            'pickups_completed_at' => $this->pickups_completed_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
        ];
    }
}
