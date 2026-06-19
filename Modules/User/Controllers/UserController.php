<?php

namespace Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\User\DTOs\UpdateProfileDTO;
use Modules\User\Requests\UpdateProfileRequest;
use Modules\User\Resources\UserResource;
use Modules\User\Services\UserService;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    public function profile(Request $request): JsonResponse
    {
        $user = $this->userService->getProfile($request->user());

        return ApiResponse::success(
            data: new UserResource($user),
            message: 'Profil muvaffaqiyatli olindi.'
        );
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->userService->updateProfile(
            UpdateProfileDTO::fromArray($request->validated()),
            $request->user()
        );

        return ApiResponse::success(
            data: new UserResource($user),
            message: 'Profil muvaffaqiyatli yangilandi.'
        );
    }
}