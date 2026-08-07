<?php

declare(strict_types=1);

namespace Modules\Order\Application\Handlers;

use App\Jobs\SendTelegramJob;
use App\Shared\Services\Push\FcmService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Admin\Infrastructure\Persistence\Models\StaffDeviceToken;
use Modules\Order\Application\Commands\AssignCourierCommand;
use Modules\Order\Domain\Entities\Order;
use Modules\Order\Domain\Repositories\OrderRepositoryInterface;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;

final class AssignCourierHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly FcmService $fcm,
    ) {}

    public function handle(AssignCourierCommand $command): OrderModel
    {
        $order = $this->orders->findById($command->orderId)
            ?? throw new ModelNotFoundException("Buyurtma topilmadi.");

        $order->assignCourier($command->courierId);

        $saved = $this->orders->save($order);

        dispatch(new SendTelegramJob(
            role:    'manager',
            message: "🚴 <b>Buyurtma #{$saved->id}</b>\n\nKuryer #{$command->courierId} tayinlandi."
        ));

        // FCM push — kuryer ilovasi butunlay yopiq bo'lsa ham xabar keladi
        $this->notifyCourier($saved);

        return OrderModel::with(['items.product'])->findOrFail($saved->id);
    }

    private function notifyCourier(Order $order): void
    {
        try {
            $courierId = $order->courierId;
            if ($courierId === null) {
                return;
            }

            $tokens = StaffDeviceToken::where('staff_id', $courierId)
                ->pluck('token');
            if ($tokens->isEmpty()) {
                return;
            }

            $address = implode(', ', array_filter([
                $order->address->street,
                $order->address->district,
                $order->address->region,
            ]));

            $amount = number_format($order->grandTotal->amount, 0, '.', ' ');
            $body   = trim($address . ' · ' . $amount . " so'm");

            $data = [
                'type'     => 'new_order',
                'order_id' => (string) $order->id,
                'title'    => "🛵 Yangi buyurtma #{$order->id}",
                'body'     => $body,
                'sound'    => 'on',
            ];

            foreach ($tokens as $token) {
                $this->fcm->send($token, $data);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
