<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Handlers;

use App\Shared\Services\Fee\OrderFeeCalculator;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Application\Queries\GetSalesTimeSeriesQuery;

/**
 * Sotuv vaqt qatori (grafik uchun). Faqat Super Admin (moliyaviy).
 * Kuryer haqi pog'onali — SQL CASE bilan har buyurtma bo'yicha yig'iladi.
 * Platform fee 15% chiziqli, shuning uchun yig'indidan hisoblanadi.
 */
final class GetSalesTimeSeriesHandler
{
    public function handle(GetSalesTimeSeriesQuery $query): array
    {
        $dateExpr = match ($query->period) {
            'monthly' => "DATE_FORMAT(created_at, '%Y-%m')",
            'weekly'  => "DATE_FORMAT(created_at, '%x-W%v')",
            default   => 'DATE(created_at)',
        };

        $builder = DB::table('orders')
            ->whereIn('status', GetDashboardProductsHandler::SOLD_STATUSES)
            ->selectRaw("{$dateExpr} as period")
            ->selectRaw('COUNT(*) as orders_count')
            ->selectRaw('COALESCE(SUM(total_price), 0) as gross_sales')
            ->selectRaw('COALESCE(SUM(' . OrderFeeCalculator::courierFeeSql() . '), 0) as courier_fees')
            ->groupByRaw($dateExpr)
            ->orderByRaw($dateExpr);

        if ($query->from !== null && $query->from !== '') {
            $builder->whereDate('created_at', '>=', $query->from);
        }
        if ($query->to !== null && $query->to !== '') {
            $builder->whereDate('created_at', '<=', $query->to);
        }

        return $builder->get()->map(function ($row): array {
            $gross         = (int) $row->gross_sales;
            $platformGross = (int) round($gross * OrderFeeCalculator::PLATFORM_FEE_RATE);
            $courier       = (int) $row->courier_fees;

            return [
                'period'           => $row->period,
                'orders_count'     => (int) $row->orders_count,
                'gross_sales'      => $gross,
                'courier_fees'     => $courier,
                'platform_fee_net' => $platformGross - $courier,
                'customer_total'   => $gross + $platformGross,
            ];
        })->all();
    }
}
