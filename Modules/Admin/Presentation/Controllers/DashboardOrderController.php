<?php

declare(strict_types=1);

namespace Modules\Admin\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Admin\Application\Handlers\GetDashboardOrderDetailHandler;
use Modules\Admin\Application\Handlers\GetDashboardOrdersHandler;
use Modules\Admin\Application\Queries\GetDashboardOrderDetailQuery;
use Modules\Admin\Application\Queries\GetDashboardOrdersQuery;
use Modules\Admin\Domain\Enums\StaffRole;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Modules\Admin\Presentation\Resources\DashboardOrderDetailResource;
use Modules\Admin\Presentation\Resources\DashboardOrderResource;

final class DashboardOrderController extends Controller
{
    public function __construct(
        private readonly GetDashboardOrdersHandler       $listHandler,
        private readonly GetDashboardOrderDetailHandler  $detailHandler,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $orders = $this->listHandler->handle(new GetDashboardOrdersQuery(
            managerScope: $this->isManager(),
            status:       $request->query('status'),
            search:       $request->query('search'),
            dateFrom:     $request->query('date_from'),
            dateTo:       $request->query('date_to'),
            perPage:      (int) $request->query('per_page', 20),
        ));

        return DashboardOrderResource::collection($orders)->response();
    }

    public function show(int $order): JsonResponse
    {
        $result = $this->detailHandler->handle(new GetDashboardOrderDetailQuery(
            id:           $order,
            managerScope: $this->isManager(),
        ));

        return DashboardOrderDetailResource::make($result)->response();
    }

    private function isManager(): bool
    {
        $user = auth('sanctum')->user();

        return $user instanceof Staff && $user->role === StaffRole::MANAGER;
    }
}
