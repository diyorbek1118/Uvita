<?php

declare(strict_types=1);

namespace Modules\Order\Application\Handlers;

use App\Jobs\SendTelegramJob;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Order\Application\Commands\ReadyToDeliverCommand;
use Modules\Order\Domain\Repositories\OrderRepositoryInterface;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;
use Modules\Courier\Application\Services\DeliveryConfirmationService;
use App\Jobs\SendSmsJob;

final class ReadyToDeliverHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly DeliveryConfirmationService $confirmation,
    ) {}

    public function handle(ReadyToDeliverCommand $command): OrderModel
    {
        $order = $this->orders->findById($command->orderId)
            ?? throw new ModelNotFoundException("Buyurtma topilmadi.");

        $order->markReadyToDeliver($command->courierNote);

        $saved = $this->orders->save($order);
        $proof = $this->confirmation->ensureForOrder($saved->id);
        $pin = $this->confirmation->reveal($proof);

        dispatch(new SendSmsJob(
            $order->phone,
            "Buyurtma #{$saved->id} tayyor. Yetkazish tasdiqlash kodi: {$pin}"
        ));

        dispatch(new SendTelegramJob(
            role: 'admin',
            message: "📦 <b>Buyurtma #{$saved->id} tayyor</b>\n\nMahsulotlar yig'ildi, kuryerga topshirishga tayyor.\n📞 {$order->phone}"
        ));

        return OrderModel::with(['items.product'])->findOrFail($saved->id);
    }
}
