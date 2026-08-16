<?php

declare(strict_types=1);

namespace Modules\Order\Application\Handlers;

use App\Jobs\SendSmsJob;
use App\Jobs\SendTelegramJob;
use App\Shared\Exceptions\DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Modules\Courier\Application\Services\DeliveryConfirmationService;
use Modules\Courier\Domain\Enums\CourierTripStatus;
use Modules\Courier\Infrastructure\Persistence\Models\CourierTrip;
use Modules\Courier\Infrastructure\Persistence\Models\CourierTripOrder;
use Modules\Order\Application\Commands\MarkDeliveredCommand;
use Modules\Order\Domain\Repositories\OrderRepositoryInterface;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;
use Modules\Payment\Domain\Enums\PaymentProvider;
use Modules\Payment\Domain\Enums\PaymentStatus;
use Modules\Payment\Infrastructure\Persistence\Models\PaymentModel;

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

            $tripOrder = CourierTripOrder::query()
                ->where('order_id', $command->orderId)
                ->lockForUpdate()
                ->first();
            if ($tripOrder !== null) {
                $trip = CourierTrip::query()->lockForUpdate()->findOrFail($tripOrder->trip_id);
                if ($trip->status !== CourierTripStatus::DELIVERING || $tripOrder->picked_up_at === null) {
                    throw new DomainException('Avval reysdagi barcha yuklarni olib bo‘ling.');
                }
                if ($command->cashReceived === null || $command->cashReceived !== $order->grandTotal->amount) {
                    throw new DomainException('Naqd qabul qilingan summa buyurtma summasiga teng bo‘lishi kerak.');
                }
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

            $cashPayment = PaymentModel::query()
                ->where('order_id', $command->orderId)
                ->where('provider', PaymentProvider::CASH->value)
                ->where('status', PaymentStatus::PENDING->value)
                ->latest('id')
                ->first();
            $cashPayment?->update([
                'status' => PaymentStatus::PAID->value,
                'transaction_id' => "cash-{$command->orderId}",
            ]);

            if ($tripOrder !== null) {
                $tripOrder->update(['delivered_at' => now()]);
                $trip->increment('cash_collected', $command->cashReceived);
                $hasRemaining = CourierTripOrder::query()
                    ->where('trip_id', $trip->id)
                    ->whereNull('delivered_at')
                    ->exists();
                if (! $hasRemaining) {
                    $trip->update([
                        'status' => CourierTripStatus::COMPLETED,
                        'completed_at' => now(),
                    ]);
                }
            }

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
