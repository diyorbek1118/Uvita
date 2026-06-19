<?php

namespace Modules\User\Services;

use Modules\User\DTOs\UpdateProfileDTO;
use Modules\User\Models\User;

class UserService
{
    public function getProfile(User $user): User
    {
        return $user;
    }

    public function updateProfile(UpdateProfileDTO $dto, User $user): User
    {
        $user->update([
            'name' => $dto->name,
        ]);

        return $user->fresh();
    }
}