<?php

declare(strict_types=1);

namespace Modules\Order\Application\Handlers;

use App\Jobs\SendSmsJob;
use App\Jobs\SendTelegramJob;
use App\Shared\Services\Settings\SettingService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Order\Application\Commands\NotFoundCommand;
use Modules\Order\Domain\Repositories\OrderRepositoryInterface;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;

final class NotFoundHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly SettingService           $settingService,
    ) {}

    public function handle(NotFoundCommand $command): OrderModel
    {
        $order = $this->orders->findById($command->orderId)
            ?? throw new ModelNotFoundException("Buyurtma topilmadi.");

        $maxAttempts = $this->settingService->maxNotFoundAttempts();
        $order->incrementNotFound($maxAttempts);

        $saved = $this->orders->save($order);

        dispatch(new SendSmsJob($order->phone, "Kuryer siz bilan bog'lana olmadi."));
        dispatch(new SendTelegramJob(
            role:    'manager',
            message: "⚠️ <b>Buyurtma #{$saved->id} — topilmadi #{$saved->not_found_count}/{$maxAttempts}</b>\n\nSabab: {$command->reason}\n📞 {$order->phone}"
        ));

        return OrderModel::with(['items.product'])->findOrFail($saved->id);
    }
}
