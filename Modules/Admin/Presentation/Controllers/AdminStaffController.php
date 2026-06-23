<?php

declare(strict_types=1);

namespace Modules\Admin\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Admin\Application\Commands\CreateStaffCommand;
use Modules\Admin\Application\Commands\DeleteStaffCommand;
use Modules\Admin\Application\Commands\ToggleStaffCommand;
use Modules\Admin\Application\Commands\UpdateStaffCommand;
use Modules\Admin\Application\DTOs\CreateStaffDTO;
use Modules\Admin\Application\DTOs\UpdateStaffDTO;
use Modules\Admin\Application\Handlers\CreateStaffHandler;
use Modules\Admin\Application\Handlers\DeleteStaffHandler;
use Modules\Admin\Application\Handlers\GetAllStaffHandler;
use Modules\Admin\Application\Handlers\GetStaffByIdHandler;
use Modules\Admin\Application\Handlers\ToggleStaffHandler;
use Modules\Admin\Application\Handlers\UpdateStaffHandler;
use Modules\Admin\Application\Queries\GetAllStaffQuery;
use Modules\Admin\Application\Queries\GetStaffByIdQuery;
use Modules\Admin\Presentation\Requests\CreateStaffRequest;
use Modules\Admin\Presentation\Requests\UpdateStaffRequest;
use Modules\Admin\Presentation\Resources\StaffResource;

final class AdminStaffController extends Controller
{
    public function __construct(
        private readonly GetAllStaffHandler   $getAllHandler,
        private readonly GetStaffByIdHandler  $getByIdHandler,
        private readonly CreateStaffHandler   $createHandler,
        private readonly UpdateStaffHandler   $updateHandler,
        private readonly DeleteStaffHandler   $deleteHandler,
        private readonly ToggleStaffHandler   $toggleHandler,
    ) {}

    public function index(): JsonResponse
    {
        $staff = $this->getAllHandler->handle(
            new GetAllStaffQuery(role: request('role'))
        );

        return StaffResource::collection($staff)->response();
    }

    public function show(int $id): JsonResponse
    {
        $staff = $this->getByIdHandler->handle(new GetStaffByIdQuery($id));

        return StaffResource::make($staff)->response();
    }

    public function store(CreateStaffRequest $request): JsonResponse
    {
        $staff = $this->createHandler->handle(
            new CreateStaffCommand(dto: CreateStaffDTO::fromRequest($request))
        );

        return StaffResource::make($staff)
            ->additional(['message' => 'Xodim yaratildi'])
            ->response()
            ->setStatusCode(201);
    }

    public function update(int $id, UpdateStaffRequest $request): JsonResponse
    {
        $staff = $this->updateHandler->handle(
            new UpdateStaffCommand(staffId: $id, dto: UpdateStaffDTO::fromRequest($request))
        );

        return StaffResource::make($staff)
            ->additional(['message' => 'Xodim yangilandi'])
            ->response();
    }

    public function destroy(int $id): JsonResponse
    {
        $this->deleteHandler->handle(new DeleteStaffCommand($id));

        return response()->json(['message' => "Xodim o'chirildi"]);
    }

    public function toggleActive(int $id): JsonResponse
    {
        $staff   = $this->toggleHandler->handle(new ToggleStaffCommand($id));
        $message = $staff->is_active ? 'Xodim faollashtirildi' : 'Xodim bloklandi';

        return StaffResource::make($staff)
            ->additional(['message' => $message])
            ->response();
    }
}
