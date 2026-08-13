<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Auth;

use Modules\Auth\Application\Contracts\TokenServiceInterface;
use Modules\User\Domain\Entities\User as UserEntity;
use Modules\User\Infrastructure\Persistence\Models\User as UserModel;

final class SanctumTokenService implements TokenServiceInterface
{
    public function createForUser(UserEntity $user): string
    {
        $model = UserModel::findOrFail($user->id);

        $expiresAt = now()->addDays((int) config('auth.customer_token_expiration_days', 30));

        return $model->createToken('auth_token', ['*'], $expiresAt)->plainTextToken;
    }
}
