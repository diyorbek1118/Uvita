<?php

declare(strict_types=1);

namespace Modules\Order\Infrastructure\Persistence\Repositories;

use Modules\Order\Domain\Entities\Order;
use Modules\Order\Domain\Entities\OrderItem;
use Modules\Order\Domain\Enums\OrderStatus;
use Modules\Order\Domain\Repositories\OrderRepositoryInterface;
use Modules\Order\Domain\ValueObjects\DeliveryAddress;
use Modules\Order\Domain\ValueObjects\DeliveryTime;
use Modules\Order\Domain\ValueObjects\Money;
use Modules\Order\Infrastructure\Persistence\Models\OrderItemModel;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;

final class EloquentOrderRepository implements OrderRepositoryInterface
{
    public function findById(int $id): ?Order
    {
        $model = OrderModel::with('items')->find($id);
        return $model ? $this->toDomain($model) : null;
    }

    public function findByUserId(int $userId): array
    {
        return OrderModel::with('items')
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($m) => $this->toDomain($m))
            ->all();
    }

    public function findByStatus(OrderStatus $status): array
    {
        return OrderModel::with('items')
            ->where('status', $status->value)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($m) => $this->toDomain($m))
            ->all();
    }

    public function findPaidOrders(): array
    {
        return $this->findByStatus(OrderStatus::PAID);
    }

    public function findReadyToDeliverOrders(): array
    {
        return $this->findByStatus(OrderStatus::READY_TO_DELIVER);
    }

    public function save(Order $order): Order
    {
        if ($order->id === null) {
            return $this->create($order);
        }
        return $this->update($order);
    }

    private function create(Order $order): Order
    {
        $model = OrderModel::create([
            'user_id'         => $order->userId,
            'courier_id'      => $order->courierId,
            'status'          => $order->status->value,
            'address'         => $order->address->toArray(),
            'phone'           => $order->phone,
            'phone_secondary' => $order->phoneSecondary,
            'delivery_time'   => $order->deliveryTime->value,
            'courier_note'    => $order->courierNote,
            'delivery_price'  => $order->deliveryPrice->amount,
            'total_price'     => $order->totalPrice->amount,
            'grand_total'     => $order->grandTotal->amount,
            'not_found_count' => $order->notFoundCount,
        ]);

        foreach ($order->items as $item) {
            OrderItemModel::create([
                'order_id'   => $model->id,
                'product_id' => $item->productId,
                'quantity'   => $item->quantity,
                'price'      => $item->price->amount,
            ]);
        }

        $model->load('items');
        return $this->toDomain($model);
    }

    private function update(Order $order): Order
    {
        $model = OrderModel::findOrFail($order->id);
        $model->update([
            'courier_id'      => $order->courierId,
            'status'          => $order->status->value,
            'courier_note'    => $order->courierNote,
            'not_found_count' => $order->notFoundCount,
        ]);
        $model->load('items');
        return $this->toDomain($model);
    }

    private function toDomain(OrderModel $model): Order
    {
        $items = $model->items->map(fn (OrderItemModel $item) => new OrderItem(
            id:        $item->id,
            orderId:   $item->order_id,
            productId: $item->product_id,
            quantity:  $item->quantity,
            price:     new Money($item->price),
        ))->all();

        return new Order(
            id:             $model->id,
            userId:         $model->user_id,
            status:         $model->status,
            address:        DeliveryAddress::fromArray($model->address),
            phone:          $model->phone,
            phoneSecondary: $model->phone_secondary,
            deliveryTime:   new DeliveryTime($model->delivery_time),
            deliveryPrice:  new Money($model->delivery_price),
            totalPrice:     new Money($model->total_price),
            grandTotal:     new Money($model->grand_total),
            items:          $items,
            courierNote:    $model->courier_note,
            courierId:      $model->courier_id,
            notFoundCount:  $model->not_found_count,
        );
    }
}
