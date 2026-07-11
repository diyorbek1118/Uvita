<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Handlers;

use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;
use Modules\Product\Infrastructure\Persistence\Models\Product as ProductModel;
use Modules\User\Infrastructure\Persistence\Models\User;

/**
 * Dashboard bosh sahifa KPI'lari — operatsion (moliyaviy summasiz).
 * Admin + Super Admin ko'radi.
 */
final class GetDashboardSummaryHandler
{
    private const LOW_STOCK_THRESHOLD = 10;

    public function handle(): array
    {
        return [
            'orders' => [
                'today'      => OrderModel::whereDate('created_at', now()->toDateString())->count(),
                'this_month' => OrderModel::whereYear('created_at', now()->year)
                    ->whereMonth('created_at', now()->month)
                    ->count(),
                'total'      => OrderModel::count(),
            ],
            'pending_approvals' => ProductModel::where('status', 'inactive')
                ->whereNotNull('manager_id')
                ->count(),
            'delivery_issues'   => OrderModel::where('status', 'delivery_issue')->count(),
            'active_couriers'   => Staff::where('role', 'courier')->where('is_active', true)->count(),
            'low_stock'         => ProductModel::where('stock', '<=', self::LOW_STOCK_THRESHOLD)->count(),
            'active_products'   => ProductModel::where('status', 'active')->count(),
            'total_customers'   => User::count(),
        ];
    }
}
