<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Handlers;

use App\Shared\Services\Fee\OrderFeeCalculator;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Application\Queries\GetRevenueBreakdownQuery;

/**
 * Umumiy tushum taqsimoti. Faqat Super Admin (moliyaviy).
 */
final class GetRevenueBreakdownHandler
{
    public function handle(GetRevenueBreakdownQuery $query): array
    {
        $builder = DB::table('orders')
            ->whereIn('status', GetDashboardProductsHandler::SOLD_STATUSES);

        if ($query->from !== null && $query->from !== '') {
            $builder->whereDate('created_at', '>=', $query->from);
        }
        if ($query->to !== null && $query->to !== '') {
            $builder->whereDate('created_at', '<=', $query->to);
        }

        $row = $builder->selectRaw('COUNT(*) as orders_count')
            ->selectRaw('COALESCE(SUM(total_price), 0) as gross_sales')
            ->selectRaw('COALESCE(SUM(' . OrderFeeCalculator::courierFeeSql() . '), 0) as courier_fees')
            ->first();

        $gross         = (int) ($row->gross_sales ?? 0);
        $platformGross = (int) round($gross * OrderFeeCalculator::PLATFORM_FEE_RATE);
        $courier       = (int) ($row->courier_fees ?? 0);

        return [
            'orders_count'       => (int) ($row->orders_count ?? 0),
            'gross_sales'        => $gross,           // mahsulotlar summasi
            'seller_payouts'     => $gross,           // sotuvchilarga
            'platform_fee_gross' => $platformGross,   // 15% yalpi
            'courier_fees'       => $courier,         // kuryerlarga
            'platform_fee_net'   => $platformGross - $courier,  // platformada qoladi
            'customer_total'     => $gross + $platformGross,    // mijozlar to'lagan jami
        ];
    }
}
