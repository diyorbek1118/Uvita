<?php

declare(strict_types=1);

namespace Modules\Order\Application\Handlers;

use App\Jobs\SendSmsJob;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Order\Application\Commands\DeliveryIssueResolveCommand;
use Modules\Order\Domain\Repositories\OrderRepositoryInterface;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;

final class ResolveIssueHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
    ) {}

    public function handle(DeliveryIssueResolveCommand $command): OrderModel
    {
        $order = $this->orders->findById($command->orderId)
            ?? throw new ModelNotFoundException("Buyurtma topilmadi.");

        $order->resolveDeliveryIssue($command->action);

        $saved = $this->orders->save($order);

        $message = $command->action === 'reschedule'
            ? "Buyurtma #{$saved->id} qayta yetkazishga rejalashtirildi."
            : "Buyurtma #{$saved->id} bekor qilindi. Administrator siz bilan bog'lanadi.";

        dispatch(new SendSmsJob($order->phone, $message));

        return OrderModel::with(['items.product'])->findOrFail($saved->id);
    }
}
