<?php

declare(strict_types=1);

namespace Modules\Courier\Application\Handlers;

use Modules\Courier\Domain\Enums\CourierPayoutStatus;
use Modules\Courier\Infrastructure\Persistence\Models\CourierPayout;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;

final class GetCourierEarningsHandler
{
    /** @return array<string, mixed> */
    public function handle(int $courierId): array
    {
        $delivered = OrderModel::query()
            ->where('courier_id', $courierId)
            ->where('status', 'delivered');
        $totalEarned = (int) (clone $delivered)->sum('courier_fee');
        $paid = (int) CourierPayout::query()
            ->where('courier_id', $courierId)
            ->where('status', CourierPayoutStatus::PAID->value)
            ->sum('amount');
        $pending = (int) CourierPayout::query()
            ->where('courier_id', $courierId)
            ->where('status', CourierPayoutStatus::PENDING->value)
            ->sum('amount');

        return [
            'today' => (int) (clone $delivered)->whereDate('delivered_at', today())->sum('courier_fee'),
            'this_week' => (int) (clone $delivered)->whereBetween('delivered_at', [now()->startOfWeek(), now()->endOfWeek()])->sum('courier_fee'),
            'this_month' => (int) (clone $delivered)->whereMonth('delivered_at', now()->month)->whereYear('delivered_at', now()->year)->sum('courier_fee'),
            'total_earned' => $totalEarned,
            'paid' => $paid,
            'pending_payout' => $pending,
            'unpaid' => max(0, $totalEarned - $paid - $pending),
            'recent_deliveries' => (clone $delivered)->latest('delivered_at')->limit(20)
                ->get(['id', 'courier_fee', 'delivered_at'])
                ->map(fn (OrderModel $order): array => [
                    'order_id' => $order->id,
                    'amount' => $order->courier_fee,
                    'delivered_at' => $order->delivered_at?->toISOString(),
                ])->all(),
        ];
    }
}
