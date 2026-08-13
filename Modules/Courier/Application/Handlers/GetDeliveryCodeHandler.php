<?php

declare(strict_types=1);

namespace Modules\Courier\Application\Handlers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Courier\Application\Services\DeliveryConfirmationService;
use Modules\Courier\Infrastructure\Persistence\Models\DeliveryProof;
use Modules\Order\Domain\Enums\OrderStatus;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;

final class GetDeliveryCodeHandler
{
    public function __construct(private readonly DeliveryConfirmationService $confirmation) {}

    /** @return array{pin:string, available:bool} */
    public function handle(int $orderId, int $userId): array
    {
        $order = OrderModel::query()->where('id', $orderId)->where('user_id', $userId)->first()
            ?? throw new ModelNotFoundException('Buyurtma topilmadi.');

        if (! in_array($order->status, [
            OrderStatus::READY_TO_DELIVER,
            OrderStatus::DELIVERING,
        ], true)) {
            throw new ModelNotFoundException('Yetkazish kodi hali mavjud emas.');
        }

        $proof = DeliveryProof::where('order_id', $orderId)->first()
            ?? throw new ModelNotFoundException('Yetkazish kodi topilmadi.');

        return ['pin' => $this->confirmation->reveal($proof), 'available' => true];
    }
}
