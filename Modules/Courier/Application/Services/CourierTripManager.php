<?php

declare(strict_types=1);

namespace Modules\Courier\Application\Services;

use App\Jobs\SendSmsJob;
use App\Shared\Exceptions\DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Modules\Courier\Domain\Enums\CourierTripStatus;
use Modules\Courier\Domain\Enums\DeliveryAssignmentStatus;
use Modules\Courier\Infrastructure\Persistence\Models\CourierTrip;
use Modules\Courier\Infrastructure\Persistence\Models\CourierTripOrder;
use Modules\Courier\Infrastructure\Persistence\Models\DeliveryAssignment;
use Modules\Order\Application\Commands\MarkDeliveredCommand;
use Modules\Order\Application\Handlers\MarkDeliveredHandler;
use Modules\Order\Domain\Enums\OrderStatus;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;

final class CourierTripManager
{
    public function __construct(
        private readonly TripBundlePlanner $planner,
        private readonly DeliveryConfirmationService $confirmation,
        private readonly MarkDeliveredHandler $markDelivered,
    ) {}

    public function preview(int $courierId, string $routeKey, ?float $capacityKg): array
    {
        return $this->planner->plan($courierId, $routeKey, $capacityKg);
    }

    public function create(int $courierId, string $routeKey, ?float $capacityKg): CourierTrip
    {
        return DB::transaction(function () use ($courierId, $routeKey, $capacityKg): CourierTrip {
            // Bir kuryer ikki parallel so'rov yuborsa ham faqat bitta reys yaratiladi.
            Staff::query()->lockForUpdate()->findOrFail($courierId);
            $active = CourierTrip::query()
                ->where('courier_id', $courierId)
                ->whereIn('status', [CourierTripStatus::PICKING_UP->value, CourierTripStatus::DELIVERING->value])
                ->lockForUpdate()
                ->exists();
            if ($active) {
                throw new DomainException('Avval faol reysni yakunlang. Bir vaqtda faqat bitta reys mumkin.');
            }

            $plan = $this->planner->plan($courierId, $routeKey, $capacityKg, true);
            $trip = CourierTrip::create([
                'courier_id' => $courierId,
                'origin_region' => $plan['route']['origin_region'],
                'destination_region' => $plan['route']['destination_region'],
                'status' => CourierTripStatus::PICKING_UP,
                'capacity_kg' => $plan['capacity_kg'],
                'total_weight_kg' => $plan['total_weight_kg'],
                'orders_count' => $plan['orders']->count(),
                'cargo_value' => $plan['cargo_value'],
                'total_courier_fee' => $plan['total_courier_fee'],
                'cash_collected' => 0,
                'accepted_at' => now(),
            ]);

            foreach ($plan['orders'] as $entry) {
                /** @var OrderModel $order */
                $order = $entry['order'];
                $order->update(['courier_id' => $courierId]);
                DeliveryAssignment::create([
                    'order_id' => $order->id,
                    'courier_id' => $courierId,
                    'assigned_by' => null,
                    'status' => DeliveryAssignmentStatus::ACCEPTED,
                    'assigned_at' => now(),
                    'responded_at' => now(),
                ]);
                CourierTripOrder::create([
                    'trip_id' => $trip->id,
                    'order_id' => $order->id,
                    'pickup_key' => $this->planner->pickupKey($order),
                    'pickup_sequence' => $plan['pickup_sequences'][$this->planner->pickupKey($order)],
                    'delivery_sequence' => $plan['delivery_sequences'][$order->id],
                    'weight_kg' => $entry['weight_kg'],
                ]);
            }

            return $this->load($trip);
        });
    }

    public function active(int $courierId): ?CourierTrip
    {
        $trip = CourierTrip::query()
            ->where('courier_id', $courierId)
            ->whereIn('status', [CourierTripStatus::PICKING_UP->value, CourierTripStatus::DELIVERING->value])
            ->latest('id')
            ->first();

        return $trip === null ? null : $this->load($trip);
    }

    public function completePickup(int $tripId, string $pickupKey, int $courierId): CourierTrip
    {
        $notify = [];
        $trip = DB::transaction(function () use ($tripId, $pickupKey, $courierId, &$notify): CourierTrip {
            $trip = $this->ownedTrip($tripId, $courierId, true);
            if ($trip->status !== CourierTripStatus::PICKING_UP) {
                throw new DomainException('Bu reysda yuklarni olish bosqichi yakunlangan.');
            }

            $loads = CourierTripOrder::query()
                ->where('trip_id', $trip->id)
                ->where('pickup_key', $pickupKey)
                ->lockForUpdate()
                ->get();
            if ($loads->isEmpty()) {
                throw new ModelNotFoundException('Yuk olish nuqtasi topilmadi.');
            }
            if ($loads->every(fn (CourierTripOrder $load): bool => $load->picked_up_at !== null)) {
                throw new DomainException('Bu nuqtadagi yuk avval olingan.');
            }
            CourierTripOrder::query()
                ->whereIn('id', $loads->pluck('id'))
                ->update(['picked_up_at' => now(), 'updated_at' => now()]);

            $remaining = CourierTripOrder::query()
                ->where('trip_id', $trip->id)
                ->whereNull('picked_up_at')
                ->exists();
            if (! $remaining) {
                $trip->update([
                    'status' => CourierTripStatus::DELIVERING,
                    'pickups_completed_at' => now(),
                ]);
                $orders = OrderModel::query()
                    ->whereIn('id', CourierTripOrder::where('trip_id', $trip->id)->pluck('order_id'))
                    ->lockForUpdate()
                    ->get();
                foreach ($orders as $order) {
                    if ($order->status !== OrderStatus::READY_TO_DELIVER) {
                        throw new DomainException("#{$order->id} buyurtma yetkazishga tayyor holatda emas.");
                    }
                    $order->update(['status' => OrderStatus::DELIVERING, 'delivering_at' => now()]);
                    $this->confirmation->ensureForOrder($order->id);
                    $notify[] = [$order->phone, "Kuryer yo'lda, tez orada yetkaziladi."];
                }
            }

            return $this->load($trip);
        });

        foreach ($notify as [$phone, $message]) {
            dispatch(new SendSmsJob($phone, $message));
        }

        return $trip;
    }

    public function cancel(int $tripId, int $courierId, string $reason): CourierTrip
    {
        return DB::transaction(function () use ($tripId, $courierId, $reason): CourierTrip {
            $trip = $this->ownedTrip($tripId, $courierId, true);
            if ($trip->status !== CourierTripStatus::PICKING_UP
                || $trip->accepted_at->lt(now()->subHours(5))) {
                throw new DomainException('Reysni faqat 5 soat ichida, yuk olish boshlanmasidan bekor qilish mumkin.');
            }
            $loads = CourierTripOrder::query()->where('trip_id', $trip->id)->lockForUpdate()->get();
            if ($loads->contains(fn (CourierTripOrder $load): bool => $load->picked_up_at !== null)) {
                throw new DomainException('Yuk olish boshlangan reysni bekor qilib bo‘lmaydi. Operator bilan bog‘laning.');
            }
            $orderIds = $loads->pluck('order_id');
            OrderModel::query()->whereIn('id', $orderIds)->update(['courier_id' => null]);
            DeliveryAssignment::query()
                ->whereIn('order_id', $orderIds)
                ->where('courier_id', $courierId)
                ->where('status', DeliveryAssignmentStatus::ACCEPTED->value)
                ->update([
                    'status' => DeliveryAssignmentStatus::CANCELLED->value,
                    'rejection_reason' => $reason,
                    'responded_at' => now(),
                ]);
            $trip->update([
                'status' => CourierTripStatus::CANCELLED,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            return $this->load($trip);
        });
    }

    public function completeDelivery(
        int $tripId,
        int $orderId,
        int $courierId,
        string $pin,
        int $cashReceived,
        ?string $recipientName,
        ?float $latitude,
        ?float $longitude,
    ): CourierTrip {
        $trip = $this->ownedTrip($tripId, $courierId);
        if ($trip->status !== CourierTripStatus::DELIVERING) {
            throw new DomainException('Avval barcha yuklarni sellerlardan olib bo‘ling.');
        }
        $tripOrder = CourierTripOrder::query()
            ->where('trip_id', $tripId)
            ->where('order_id', $orderId)
            ->firstOrFail();
        $order = OrderModel::query()->where('courier_id', $courierId)->findOrFail($orderId);
        if ($tripOrder->picked_up_at === null) {
            throw new DomainException('Bu buyurtma yuki hali olinmagan.');
        }
        if ($cashReceived !== (int) $order->grand_total) {
            throw new DomainException('Naqd qabul qilingan summa buyurtma summasiga teng bo‘lishi kerak.');
        }

        $this->markDelivered->handle(new MarkDeliveredCommand(
            orderId: $orderId,
            courierId: $courierId,
            pin: $pin,
            recipientName: $recipientName,
            latitude: $latitude,
            longitude: $longitude,
            cashReceived: $cashReceived,
        ));

        return $this->load(CourierTrip::findOrFail($tripId));
    }

    private function ownedTrip(int $tripId, int $courierId, bool $lock = false): CourierTrip
    {
        $query = CourierTrip::query()->where('courier_id', $courierId);
        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->findOrFail($tripId);
    }

    private function load(CourierTrip $trip): CourierTrip
    {
        return $trip->fresh()->load([
            'tripOrders.order.items.product.sellerProfile',
            'tripOrders.order.deliveryAssignments',
        ]);
    }
}
