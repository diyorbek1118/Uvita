<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Handlers;

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
            ->selectRaw('COALESCE(SUM(service_fee), 0) as platform_fee_gross')
            ->selectRaw('COALESCE(SUM(courier_fee), 0) as courier_fees')
            ->first();

        $gross = (int) ($row->gross_sales ?? 0);
        $platformGross = (int) ($row->platform_fee_gross ?? 0);
        $courier = (int) ($row->courier_fees ?? 0);
        $tax = (int) round($gross * 0.01);
        $payment = (int) round($gross * 0.03);
        $sellerPayouts = $gross - $platformGross - $courier - $tax - $payment;

        return [
            'orders_count' => (int) ($row->orders_count ?? 0),
            'gross_sales' => $gross,           // mahsulotlar summasi
            'seller_payouts' => $sellerPayouts,
            'platform_fee_gross' => $platformGross,   // platforma 10%
            'courier_fees' => $courier,         // kuryerlarga
            'platform_fee_net' => $platformGross + $tax + $payment,
            'customer_total' => $gross,
        ];
    }
}
