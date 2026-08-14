<?php

declare(strict_types=1);

namespace Modules\Order\Application\Handlers;

use App\Jobs\SendSmsJob;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Order\Application\Commands\ConfirmOrderCommand;
use Modules\Order\Domain\Repositories\OrderRepositoryInterface;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;
use Modules\Payment\Domain\Enums\PaymentProvider;
use Modules\Payment\Infrastructure\Persistence\Models\PaymentModel;

final class ConfirmOrderHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
    ) {}

    public function handle(ConfirmOrderCommand $command): OrderModel
    {
        $order = $this->orders->findById($command->orderId)
            ?? throw new ModelNotFoundException("Buyurtma topilmadi.");

        $payment = PaymentModel::where('order_id', $command->orderId)->latest('id')->first();
        if ($payment?->provider === PaymentProvider::CASH) {
            $order->confirmCashOnDelivery();
        } else {
            $order->confirm();
        }

        $saved = $this->orders->save($order);

        dispatch(new SendSmsJob($order->phone, "Buyurtma #{$saved->id} tasdiqlandi."));

        return OrderModel::with(['items.product'])->findOrFail($saved->id);
    }
}
