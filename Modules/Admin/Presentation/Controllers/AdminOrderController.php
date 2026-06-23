<?php

declare(strict_types=1);

namespace Modules\Admin\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Admin\Application\Handlers\GetDeliveryIssueOrdersHandler;
use Modules\Admin\Application\Handlers\GetOrderStatsHandler;
use Modules\Admin\Application\Queries\GetDeliveryIssueOrdersQuery;
use Modules\Order\Presentation\Resources\OrderResource;

final class AdminOrderController extends Controller
{
    public function __construct(
        private readonly GetDeliveryIssueOrdersHandler $deliveryIssueHandler,
        private readonly GetOrderStatsHandler          $statsHandler,
    ) {}

    public function deliveryIssues(): JsonResponse
    {
        $orders = $this->deliveryIssueHandler->handle(new GetDeliveryIssueOrdersQuery());

        return OrderResource::collection($orders)->response();
    }

    public function stats(): JsonResponse
    {
        $stats = $this->statsHandler->handle();

        return response()->json(['data' => $stats]);
    }
}
