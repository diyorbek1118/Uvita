<?php

declare(strict_types=1);

namespace Modules\Admin\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Admin\Application\Handlers\GetAllUsersHandler;
use Modules\Admin\Application\Handlers\GetUserByIdHandler;
use Modules\Admin\Application\Queries\GetAllUsersQuery;
use Modules\Admin\Application\Queries\GetUserByIdQuery;
use Modules\Admin\Presentation\Resources\AdminUserDetailResource;
use Modules\Admin\Presentation\Resources\AdminUserResource;

final class AdminUserController extends Controller
{
    public function __construct(
        private readonly GetAllUsersHandler  $getAllHandler,
        private readonly GetUserByIdHandler  $getByIdHandler,
    ) {}

    public function index(): JsonResponse
    {
        $users = $this->getAllHandler->handle(
            new GetAllUsersQuery(search: request('search'))
        );

        return AdminUserResource::collection($users)->response();
    }

    public function show(int $id): JsonResponse
    {
        $user = $this->getByIdHandler->handle(new GetUserByIdQuery($id));

        return AdminUserDetailResource::make($user)->response();
    }
}
