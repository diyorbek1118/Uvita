<?php

declare(strict_types=1);

namespace Modules\Order\Application\Handlers;

use App\Jobs\SendTelegramJob;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Order\Application\Commands\AssignCourierCommand;
use Modules\Order\Domain\Repositories\OrderRepositoryInterface;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;

final class AssignCourierHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
    ) {}

    public function handle(AssignCourierCommand $command): OrderModel
    {
        $order = $this->orders->findById($command->orderId)
            ?? throw new ModelNotFoundException("Buyurtma topilmadi.");

        $order->assignCourier($command->courierId);

        $saved = $this->orders->save($order);

        dispatch(new SendTelegramJob(
            chatId:  (string) config('telegram.manager_chat_id', ''),
            message: "🚴 <b>Buyurtma #{$saved->id}</b>\n\nKuryer #{$command->courierId} tayinlandi."
        ));

        return OrderModel::with(['items.product'])->findOrFail($saved->id);
    }
}
