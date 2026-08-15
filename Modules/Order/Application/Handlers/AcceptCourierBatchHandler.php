<?php

declare(strict_types=1);

namespace Modules\Order\Application\Handlers;

use App\Shared\Exceptions\DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Courier\Application\Services\CourierRouteService;
use Modules\Courier\Domain\Enums\DeliveryAssignmentStatus;
use Modules\Courier\Infrastructure\Persistence\Models\DeliveryAssignment;
use Modules\Order\Application\Commands\AcceptCourierBatchCommand;
use Modules\Order\Domain\Enums\OrderStatus;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;

final class AcceptCourierBatchHandler
{
    public function __construct(private readonly CourierRouteService $routes) {}

    /** @return Collection<int, OrderModel> */
    public function handle(AcceptCourierBatchCommand $command): Collection
    {
        $orderIds = collect($command->orderIds)->map(fn ($id): int => (int) $id)->unique()->sort()->values();

        return DB::transaction(function () use ($command, $orderIds): Collection {
            $orders = OrderModel::query()
                ->with(['items.product.sellerProfile'])
                ->whereIn('id', $orderIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($orders->count() !== $orderIds->count()) {
                throw new DomainException('Tanlangan buyurtmalardan biri topilmadi. Ro‘yxatni yangilang.');
            }

            if ($orders->contains(fn (OrderModel $order): bool => (
                $order->status !== OrderStatus::READY_TO_DELIVER || $order->courier_id !== null
            ))) {
                throw new DomainException('Tanlangan buyurtmalardan birini boshqa kuryer olib bo‘lgan. Ro‘yxatni yangilang.');
            }

            if (! $this->routes->sameRoute($orders)) {
                throw new DomainException('Faqat bir xil jo‘nash va yetkazish yo‘nalishidagi buyurtmalarni birga olish mumkin.');
            }

            foreach ($orders as $order) {
                $order->update(['courier_id' => $command->courierId]);

                DeliveryAssignment::create([
                    'order_id' => $order->id,
                    'courier_id' => $command->courierId,
                    'assigned_by' => null,
                    'status' => DeliveryAssignmentStatus::ACCEPTED,
                    'assigned_at' => now(),
                    'responded_at' => now(),
                ]);
            }

            return OrderModel::query()
                ->with(['items.product.sellerProfile', 'deliveryAssignments'])
                ->whereIn('id', $orderIds)
                ->orderBy('id')
                ->get();
        });
    }
}
