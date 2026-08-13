<?php

declare(strict_types=1);

namespace Modules\Order\Application\Handlers;

use App\Jobs\SendSmsJob;
use App\Jobs\SendTelegramJob;
use App\Shared\Exceptions\DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Modules\Courier\Application\Services\DeliveryConfirmationService;
use Modules\Order\Application\Commands\MarkDeliveredCommand;
use Modules\Order\Domain\Repositories\OrderRepositoryInterface;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;

final class MarkDeliveredHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly DeliveryConfirmationService $confirmation,
    ) {}

    public function handle(MarkDeliveredCommand $command): OrderModel
    {
        [$saved, $confirmationError] = DB::transaction(function () use ($command): array {
            OrderModel::query()->lockForUpdate()->findOrFail($command->orderId);
            $order = $this->orders->findById($command->orderId)
                ?? throw new ModelNotFoundException('Buyurtma topilmadi.');

            if ($order->courierId !== $command->courierId) {
                throw new ModelNotFoundException('Buyurtma topilmadi.');
            }

            if (! preg_match('/^\d{4}$/', $command->pin)) {
                throw new DomainException('Yetkazish PIN kodi majburiy.');
            }

            try {
                $this->confirmation->verify(
                    $command->orderId,
                    $command->courierId,
                    $command->pin,
                    $command->recipientName,
                    $command->latitude,
                    $command->longitude,
                );
            } catch (DomainException $exception) {
                // Noto'g'ri PIN urinishini transaction ichida saqlab qolamiz.
                return [null, $exception->getMessage()];
            }

            $order->markDelivered();

            return [$this->orders->save($order), null];
        });

        if ($confirmationError !== null) {
            throw new DomainException($confirmationError);
        }

        if ($saved === null) {
            throw new DomainException('Yetkazishni tasdiqlab bo‘lmadi.');
        }

        $savedModel = OrderModel::findOrFail($saved->id);
        dispatch(new SendSmsJob($savedModel->phone, "Buyurtma #{$saved->id} muvaffaqiyatli yetkazildi."));
        dispatch(new SendTelegramJob(
            role: 'manager',
            message: "✅ <b>Buyurtma #{$saved->id} yetkazildi</b>\n\n📞 {$savedModel->phone}"
        ));

        // TODO: dispatch(new SendReviewRequestJob($saved->id))->delay(now()->addHours(24));

        return OrderModel::with(['items.product', 'deliveryProof'])->findOrFail($saved->id);
    }
}
