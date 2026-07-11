<?php

declare(strict_types=1);

namespace Modules\Admin\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Admin\Application\Handlers\GetDashboardSummaryHandler;
use Modules\Admin\Application\Handlers\GetOrderStatsHandler;
use Modules\Admin\Application\Handlers\GetRevenueBreakdownHandler;
use Modules\Admin\Application\Handlers\GetSalesTimeSeriesHandler;
use Modules\Admin\Application\Handlers\GetTopProductsHandler;
use Modules\Admin\Application\Queries\GetRevenueBreakdownQuery;
use Modules\Admin\Application\Queries\GetSalesTimeSeriesQuery;
use Modules\Admin\Application\Queries\GetTopProductsQuery;

final class DashboardAnalyticsController extends Controller
{
    public function __construct(
        private readonly GetDashboardSummaryHandler   $summaryHandler,
        private readonly GetOrderStatsHandler         $orderStatsHandler,
        private readonly GetTopProductsHandler        $topProductsHandler,
        private readonly GetSalesTimeSeriesHandler    $salesHandler,
        private readonly GetRevenueBreakdownHandler   $revenueHandler,
    ) {}

    // --- Operatsion (admin + super) ---

    public function summary(): JsonResponse
    {
        return response()->json(['data' => $this->summaryHandler->handle()]);
    }

    public function orderStatus(): JsonResponse
    {
        return response()->json(['data' => $this->orderStatsHandler->handle()]);
    }

    public function topProducts(Request $request): JsonResponse
    {
        $result = $this->topProductsHandler->handle(
            new GetTopProductsQuery(limit: (int) $request->query('limit', 10))
        );

        return response()->json(['data' => $result]);
    }

    // --- Moliyaviy (faqat super admin) ---

    public function sales(Request $request): JsonResponse
    {
        $result = $this->salesHandler->handle(new GetSalesTimeSeriesQuery(
            period: $request->query('period', 'daily'),
            from:   $request->query('from'),
            to:     $request->query('to'),
        ));

        return response()->json(['data' => $result]);
    }

    public function revenue(Request $request): JsonResponse
    {
        $result = $this->revenueHandler->handle(new GetRevenueBreakdownQuery(
            from: $request->query('from'),
            to:   $request->query('to'),
        ));

        return response()->json(['data' => $result]);
    }
}
