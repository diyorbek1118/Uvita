<?php

declare(strict_types=1);

namespace Modules\Order\Application\Handlers;

use App\Jobs\SendSmsJob;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Order\Application\Commands\CancelOrderCommand;
use Modules\Order\Domain\Repositories\OrderRepositoryInterface;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;

final class CancelOrderHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
    ) {}

    public function handle(CancelOrderCommand $command): OrderModel
    {
        $order = $this->orders->findById($command->orderId)
            ?? throw new ModelNotFoundException("Buyurtma topilmadi.");

        // Faqat o'z buyurtmasini bekor qila oladi
        if ($order->userId !== $command->userId) {
            abort(403, "Ruxsat yo'q.");
        }

        $order->cancel();

        $saved = $this->orders->save($order);

        dispatch(new SendSmsJob($order->phone, "Buyurtma #{$saved->id} bekor qilindi."));

        return OrderModel::with(['items.product'])->findOrFail($saved->id);
    }
}
