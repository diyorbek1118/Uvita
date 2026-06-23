<?php

declare(strict_types=1);

namespace Modules\Order\Application\Handlers;

use App\Jobs\SendSmsJob;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Order\Application\Commands\MarkDeliveringCommand;
use Modules\Order\Domain\Repositories\OrderRepositoryInterface;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;

final class MarkDeliveringHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
    ) {}

    public function handle(MarkDeliveringCommand $command): OrderModel
    {
        $order = $this->orders->findById($command->orderId)
            ?? throw new ModelNotFoundException("Buyurtma topilmadi.");

        $order->markDelivering($command->courierId);

        $saved = $this->orders->save($order);

        dispatch(new SendSmsJob($order->phone, "Kuryer yo'lda, tez orada yetkaziladi."));

        return OrderModel::with(['items.product'])->findOrFail($saved->id);
    }
}
