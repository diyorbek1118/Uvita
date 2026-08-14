<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Handlers;

use Carbon\CarbonImmutable;
use Modules\Admin\Application\Queries\GetSalesTimeSeriesQuery;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;

/**
 * Sotuv vaqt qatori (grafik uchun). Faqat Super Admin (moliyaviy).
 * Mijoz jami mahsulot narxini to'laydi; ichki ushlanmalar shu summadan olinadi.
 */
final class GetSalesTimeSeriesHandler
{
    public function handle(GetSalesTimeSeriesQuery $query): array
    {
        $period = in_array($query->period, ['daily', 'weekly', 'monthly'], true)
            ? $query->period
            : 'daily';

        $builder = OrderModel::query()
            ->whereIn('status', GetDashboardProductsHandler::SOLD_STATUSES)
            ->orderBy('created_at');

        if ($query->from !== null && $query->from !== '') {
            $builder->whereDate('created_at', '>=', $query->from);
        }
        if ($query->to !== null && $query->to !== '') {
            $builder->whereDate('created_at', '<=', $query->to);
        }

        return $builder->get()
            ->groupBy(fn (OrderModel $order): string => $this->periodKey(
                CarbonImmutable::instance($order->created_at),
                $period,
            ))
            ->map(function ($orders, string $periodKey): array {
                $gross = (int) $orders->sum('total_price');
                $platformGross = (int) $orders->sum('service_fee');
                $courier = (int) $orders->sum('courier_fee');
                $tax = (int) round($gross * 0.01);
                $payment = (int) round($gross * 0.03);

                return [
                    'period' => $periodKey,
                    'orders_count' => $orders->count(),
                    'gross_sales' => $gross,
                    'courier_fees' => $courier,
                    'platform_fee_net' => $platformGross + $tax + $payment,
                    'customer_total' => $gross,
                ];
            })
            ->values()
            ->all();
    }

    private function periodKey(CarbonImmutable $date, string $period): string
    {
        return match ($period) {
            'monthly' => $date->format('Y-m'),
            'weekly' => $date->format('o-\\WW'),
            default => $date->format('Y-m-d'),
        };
    }
}
