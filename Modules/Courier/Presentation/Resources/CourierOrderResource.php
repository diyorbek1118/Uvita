<?php

declare(strict_types=1);

namespace Modules\Courier\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Courier\Domain\Enums\CourierTripStatus;
use Modules\Courier\Domain\Enums\DeliveryAssignmentStatus;
use Modules\Order\Domain\Enums\OrderStatus;

final class CourierOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $completed = $this->status === OrderStatus::DELIVERED;
        $address = $this->address ?? [];
        $tripPickingUp = $this->relationLoaded('tripOrder')
            && $this->tripOrder?->relationLoaded('trip')
            && $this->tripOrder?->trip?->status === CourierTripStatus::PICKING_UP;

        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'phone' => $tripPickingUp ? null : ($completed ? $this->maskPhone((string) $this->phone) : $this->phone),
            'phone_secondary' => $completed || $tripPickingUp ? null : $this->phone_secondary,
            'address' => $tripPickingUp ? null : ($completed ? [
                'region' => $address['region'] ?? null,
                'district' => $address['district'] ?? null,
            ] : $address),
            'destination_area' => $tripPickingUp ? [
                'region' => $address['region'] ?? null,
                'district' => $address['district'] ?? null,
            ] : null,
            'delivery_location' => ! $completed && ! $tripPickingUp && $this->delivery_latitude !== null ? [
                'latitude' => (float) $this->delivery_latitude,
                'longitude' => (float) $this->delivery_longitude,
            ] : null,
            'delivery_time' => $this->delivery_time,
            'courier_note' => $this->courier_note,
            'grand_total' => $this->grand_total,
            'courier_fee' => $this->courier_fee,
            'not_found_count' => $this->not_found_count,
            'pickup_points' => $this->whenLoaded('items', function () {
                return $this->items
                    ->map(function ($item): array {
                        $profile = $item->product?->sellerProfile;

                        return [
                            'key' => $profile?->id !== null ? 'seller-'.$profile->id : 'product-'.$item->product_id,
                            'business_name' => $profile?->business_name,
                            'phone' => $profile?->phone,
                            'region' => $profile?->region ?: $item->product?->origin_region,
                            'district' => $profile?->district,
                            'address' => $profile?->address,
                        ];
                    })
                    ->unique('key')
                    ->values();
            }),
            'assignment' => $this->whenLoaded('deliveryAssignments', function (): ?array {
                $assignment = $this->deliveryAssignments->sortByDesc('id')->first();

                return $assignment ? [
                    'id' => $assignment->id,
                    'status' => $assignment->status->value,
                    'rejection_reason' => $assignment->rejection_reason,
                    'assigned_at' => $assignment->assigned_at?->toISOString(),
                    'responded_at' => $assignment->responded_at?->toISOString(),
                    'cancel_until' => $assignment->status === DeliveryAssignmentStatus::ACCEPTED
                        ? $assignment->assigned_at?->copy()->addHours(5)->toISOString()
                        : null,
                    'can_cancel' => $this->status === OrderStatus::READY_TO_DELIVER
                        && $assignment->status === DeliveryAssignmentStatus::ACCEPTED
                        && $assignment->assigned_at?->gte(now()->subHours(5)),
                ] : null;
            }),
            'attempts' => $this->whenLoaded('deliveryAttempts', fn () => $this->deliveryAttempts->map(fn ($attempt): array => [
                'number' => $attempt->attempt_number,
                'reason_code' => $attempt->reason_code->value,
                'reason_note' => $attempt->reason_note,
                'attempted_at' => $attempt->attempted_at?->toISOString(),
            ])->values()),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item): array => [
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name,
                'quantity' => $item->quantity,
                'unit' => $item->product?->unit ?: 'dona',
                'price' => $item->price,
                'subtotal' => $item->price * $item->quantity,
            ])->values()),
            'ready_at' => $this->ready_at?->toISOString(),
            'delivering_at' => $this->delivering_at?->toISOString(),
            'delivered_at' => $this->delivered_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }

    private function maskPhone(string $phone): string
    {
        return mb_strlen($phone) > 7
            ? mb_substr($phone, 0, 4).'***'.mb_substr($phone, -4)
            : '***';
    }
}
