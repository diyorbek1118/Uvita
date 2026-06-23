<?php

declare(strict_types=1);

namespace Modules\Order\Application\Handlers;

use App\Jobs\SendTelegramJob;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Order\Application\Commands\ReadyToDeliverCommand;
use Modules\Order\Domain\Repositories\OrderRepositoryInterface;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;

final class ReadyToDeliverHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
    ) {}

    public function handle(ReadyToDeliverCommand $command): OrderModel
    {
        $order = $this->orders->findById($command->orderId)
            ?? throw new ModelNotFoundException("Buyurtma topilmadi.");

        $order->markReadyToDeliver($command->courierNote);

        $saved = $this->orders->save($order);

        dispatch(new SendTelegramJob(
            chatId:  (string) config('telegram.manager_chat_id', ''),
            message: "📦 <b>Buyurtma #{$saved->id} tayyor</b>\n\nMahsulotlar yig'ildi, kuryerga topshirishga tayyor.\n📞 {$order->phone}"
        ));

        return OrderModel::with(['items.product'])->findOrFail($saved->id);
    }
}
