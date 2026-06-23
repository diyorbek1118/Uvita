<?php

declare(strict_types=1);

namespace Modules\Admin\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Admin\Application\Commands\ToggleCourierActiveCommand;
use Modules\Admin\Application\Handlers\GetAllCouriersHandler;
use Modules\Admin\Application\Handlers\GetAvailableCouriersHandler;
use Modules\Admin\Application\Handlers\GetCourierByIdHandler;
use Modules\Admin\Application\Handlers\ToggleCourierActiveHandler;
use Modules\Admin\Application\Queries\GetCourierByIdQuery;
use Modules\Admin\Presentation\Resources\AdminCourierResource;

final class AdminCourierController extends Controller
{
    public function __construct(
        private readonly GetAvailableCouriersHandler $availableHandler,
        private readonly GetAllCouriersHandler       $allHandler,
        private readonly GetCourierByIdHandler       $byIdHandler,
        private readonly ToggleCourierActiveHandler  $toggleHandler,
    ) {}

    public function available(): JsonResponse
    {
        $couriers = $this->availableHandler->handle();

        return AdminCourierResource::collection($couriers)->response();
    }

    public function index(): JsonResponse
    {
        $couriers = $this->allHandler->handle();

        return AdminCourierResource::collection($couriers)->response();
    }

    public function show(int $id): JsonResponse
    {
        $courier = $this->byIdHandler->handle(new GetCourierByIdQuery($id));

        return AdminCourierResource::make($courier)->response();
    }

    public function toggleActive(int $id): JsonResponse
    {
        $courier = $this->toggleHandler->handle(new ToggleCourierActiveCommand($id));

        $message = $courier->is_active ? 'Kuryer faollashtirildi' : "Kuryer o'chirildi";

        return AdminCourierResource::make($courier)
            ->additional(['message' => $message])
            ->response();
    }
}
