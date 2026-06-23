<?php

declare(strict_types=1);

namespace Modules\Order\Application\Handlers;

use App\Jobs\SendSmsJob;
use App\Jobs\SendTelegramJob;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Order\Application\Commands\MarkDeliveredCommand;
use Modules\Order\Domain\Repositories\OrderRepositoryInterface;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;

final class MarkDeliveredHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
    ) {}

    public function handle(MarkDeliveredCommand $command): OrderModel
    {
        $order = $this->orders->findById($command->orderId)
            ?? throw new ModelNotFoundException("Buyurtma topilmadi.");

        $order->markDelivered();

        $saved = $this->orders->save($order);

        dispatch(new SendSmsJob($order->phone, "Buyurtma #{$saved->id} muvaffaqiyatli yetkazildi."));
        dispatch(new SendTelegramJob(
            role:    'manager',
            message: "✅ <b>Buyurtma #{$saved->id} yetkazildi</b>\n\n📞 {$order->phone}"
        ));

        // TODO: dispatch(new SendReviewRequestJob($saved->id))->delay(now()->addHours(24));

        return OrderModel::with(['items.product'])->findOrFail($saved->id);
    }
}
