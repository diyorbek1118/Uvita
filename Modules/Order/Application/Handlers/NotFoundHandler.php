<?php

declare(strict_types=1);

namespace Modules\Order\Application\Handlers;

use App\Jobs\SendSmsJob;
use App\Jobs\SendTelegramJob;
use App\Shared\Services\Settings\SettingService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Modules\Courier\Domain\Enums\DeliveryAttemptReason;
use Modules\Courier\Infrastructure\Persistence\Models\DeliveryAttempt;
use Modules\Order\Application\Commands\NotFoundCommand;
use Modules\Order\Domain\Repositories\OrderRepositoryInterface;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;

final class NotFoundHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly SettingService $settingService,
    ) {}

    public function handle(NotFoundCommand $command): OrderModel
    {
        [$saved, $phone, $maxAttempts] = DB::transaction(function () use ($command): array {
            OrderModel::query()->lockForUpdate()->findOrFail($command->orderId);
            $order = $this->orders->findById($command->orderId)
                ?? throw new ModelNotFoundException('Buyurtma topilmadi.');

            if ($order->courierId !== $command->courierId) {
                throw new ModelNotFoundException('Buyurtma topilmadi.');
            }

            $maxAttempts = $this->settingService->maxNotFoundAttempts();
            $order->incrementNotFound($maxAttempts);
            $saved = $this->orders->save($order);

            DeliveryAttempt::create([
                'order_id' => $saved->id,
                'courier_id' => $command->courierId,
                'attempt_number' => $saved->notFoundCount,
                'reason_code' => DeliveryAttemptReason::from($command->reasonCode),
                'reason_note' => $command->reasonNote,
                'latitude' => $command->latitude,
                'longitude' => $command->longitude,
                'attempted_at' => now(),
            ]);

            return [$saved, $order->phone, $maxAttempts];
        });

        $customerMessage = $saved->status->value === 'delivery_issue'
            ? 'Yetkazishda muammo yuzaga keldi. Administrator siz bilan bog‘lanadi.'
            : "Kuryer siz bilan bog'lana olmadi. Iltimos telefonga chiqing.";
        dispatch(new SendSmsJob($phone, $customerMessage));
        dispatch(new SendTelegramJob(
            role: 'admin',
            message: "⚠️ <b>Buyurtma #{$saved->id} — topilmadi #{$saved->notFoundCount}/{$maxAttempts}</b>\n\nSabab: {$command->reasonCode}\nIzoh: {$command->reasonNote}\n📞 {$phone}"
        ));

        return OrderModel::with(['items.product', 'deliveryAttempts'])->findOrFail($saved->id);
    }
}
