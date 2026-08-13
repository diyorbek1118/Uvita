<?php

declare(strict_types=1);

namespace Modules\User\Presentation\Controllers;

use App\Shared\Services\Upload\ImageUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\User\Infrastructure\Persistence\Models\User;
use Modules\User\Presentation\Requests\UpdateProfileRequest;
use Modules\User\Presentation\Resources\UserResource;

final class UserController extends Controller
{
    public function profile(): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        return UserResource::make($this->withOrderStats($user))->response();
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $avatar = $user->avatar;
        if ($request->hasFile('avatar')) {
            $avatar = app(ImageUploadService::class)->store($request->file('avatar'), 'avatars');
        } elseif ($request->exists('avatar') && $request->input('avatar') === null) {
            $avatar = null;
        }

        $data = [];
        foreach (['name', 'surname', 'region', 'district', 'address', 'lat', 'lng'] as $field) {
            if ($request->exists($field)) {
                $value = $request->input($field);
                $data[$field] = ($value === '' || $value === null) ? null : $value;
            }
        }
        if ($request->hasFile('avatar') || $request->exists('avatar')) {
            $data['avatar'] = $avatar;
        }

        $user->update($data);

        return UserResource::make($this->withOrderStats($user))
            ->additional(['message' => 'Profil yangilandi'])
            ->response();
    }

    private function withOrderStats(User $user): User
    {
        return $user->loadCount([
            'orders as orders_total',
            'orders as orders_delivered' => fn ($query) => $query->where('status', 'delivered'),
            'orders as orders_pending' => fn ($query) => $query->where('status', 'pending'),
            'orders as orders_delivering' => fn ($query) => $query->where('status', 'delivering'),
        ]);
    }
}
