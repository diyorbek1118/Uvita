<?php

declare(strict_types=1);

namespace Modules\Order\Application\Handlers;

use App\Jobs\SendSmsJob;
use App\Jobs\SendTelegramJob;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Order\Application\Commands\NotFoundCommand;
use Modules\Order\Domain\Repositories\OrderRepositoryInterface;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;

final class NotFoundHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
    ) {}

    public function handle(NotFoundCommand $command): OrderModel
    {
        $order = $this->orders->findById($command->orderId)
            ?? throw new ModelNotFoundException("Buyurtma topilmadi.");

        $order->incrementNotFound();

        $saved = $this->orders->save($order);

        dispatch(new SendSmsJob($order->phone, "Kuryer siz bilan bog'lana olmadi."));
        dispatch(new SendTelegramJob(
            chatId:  (string) config('telegram.manager_chat_id', ''),
            message: "⚠️ <b>Buyurtma #{$saved->id} — topilmadi #{$saved->not_found_count}/3</b>\n\nSabab: {$command->reason}\n📞 {$order->phone}"
        ));

        return OrderModel::with(['items.product'])->findOrFail($saved->id);
    }
}
