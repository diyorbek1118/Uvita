<?php

declare(strict_types=1);

namespace Modules\Courier\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Courier\Application\Handlers\GetCourierHistoryHandler;
use Modules\Courier\Application\Handlers\GetCourierProfileHandler;
use Modules\Courier\Application\Handlers\GetCourierStatsHandler;
use Modules\Courier\Application\Queries\GetCourierHistoryQuery;
use Modules\Courier\Application\Queries\GetCourierProfileQuery;
use Modules\Courier\Application\Queries\GetCourierStatsQuery;
use Modules\Courier\Presentation\Resources\CourierProfileResource;
use Modules\Courier\Presentation\Resources\CourierStatsResource;
use Modules\Order\Presentation\Resources\OrderResource;

final class CourierController extends Controller
{
    public function __construct(
        private readonly GetCourierProfileHandler $profileHandler,
        private readonly GetCourierHistoryHandler $historyHandler,
        private readonly GetCourierStatsHandler   $statsHandler,
    ) {}

    public function profile(): JsonResponse
    {
        $courier = $this->profileHandler->handle(
            new GetCourierProfileQuery(auth('sanctum')->id())
        );

        return CourierProfileResource::make($courier)->response();
    }

    public function history(): JsonResponse
    {
        $orders = $this->historyHandler->handle(
            new GetCourierHistoryQuery(auth('sanctum')->id())
        );

        return OrderResource::collection($orders)->response();
    }

    public function stats(): JsonResponse
    {
        $stats = $this->statsHandler->handle(
            new GetCourierStatsQuery(auth('sanctum')->id())
        );

        return response()->json(['data' => (new CourierStatsResource($stats))->toArray(request())]);
    }
}
