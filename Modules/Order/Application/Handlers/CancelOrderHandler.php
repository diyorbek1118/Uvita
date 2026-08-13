<?php

declare(strict_types=1);

namespace Modules\Order\Application\Handlers;

use App\Jobs\SendSmsJob;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Modules\Order\Application\Commands\CancelOrderCommand;
use Modules\Order\Domain\Repositories\OrderRepositoryInterface;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;
use Modules\Payment\Domain\Enums\PaymentStatus;
use Modules\Payment\Infrastructure\Persistence\Models\PaymentModel;

final class CancelOrderHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
    ) {}

    public function handle(CancelOrderCommand $command): OrderModel
    {
        [$saved, $phone] = DB::transaction(function () use ($command): array {
            OrderModel::query()->lockForUpdate()->findOrFail($command->orderId);

            $order = $this->orders->findById($command->orderId)
                ?? throw new ModelNotFoundException('Buyurtma topilmadi.');

            // Faqat o'z buyurtmasini bekor qila oladi
            if ($order->userId !== $command->userId) {
                abort(403, "Ruxsat yo'q.");
            }

            $order->cancel();
            $saved = $this->orders->save($order);

            PaymentModel::query()
                ->where('order_id', $command->orderId)
                ->where('status', PaymentStatus::PENDING->value)
                ->lockForUpdate()
                ->update(['status' => PaymentStatus::CANCELLED->value]);

            return [$saved, $order->phone];
        });

        dispatch(new SendSmsJob($phone, "Buyurtma #{$saved->id} bekor qilindi."));

        return OrderModel::with(['items.product', 'latestPayment'])->findOrFail($saved->id);
    }
}
