<?php

declare(strict_types=1);

namespace Modules\User\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\User\Infrastructure\Persistence\Models\User;
use Modules\User\Presentation\Requests\UpdateProfileRequest;
use Modules\User\Presentation\Resources\UserResource;

final class UserController extends Controller
{
    public function profile(): JsonResponse
    {
        return UserResource::make(auth()->user())->response();
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $user->update(['name' => $request->input('name')]);

        return UserResource::make($user)
            ->additional(['message' => 'Profil yangilandi'])
            ->response();
    }
}
