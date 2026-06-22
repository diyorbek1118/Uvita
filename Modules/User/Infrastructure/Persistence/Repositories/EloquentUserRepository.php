<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Persistence\Repositories;

use Modules\User\Domain\Entities\User as UserEntity;
use Modules\User\Domain\Repositories\UserRepositoryInterface;
use Modules\User\Infrastructure\Persistence\Models\User as UserModel;

final class EloquentUserRepository implements UserRepositoryInterface
{
    public function findById(int $id): ?UserEntity
    {
        $model = UserModel::find($id);

        return $model ? $this->toEntity($model) : null;
    }

    public function findByPhone(string $phone): ?UserEntity
    {
        $model = UserModel::where('phone', $phone)->first();

        return $model ? $this->toEntity($model) : null;
    }

    public function save(UserEntity $user): UserEntity
    {
        $model = $user->id
            ? UserModel::findOrFail($user->id)
            : new UserModel();

        $model->phone = $user->phone;
        $model->name  = $user->name;
        $model->save();

        return $this->toEntity($model);
    }

    private function toEntity(UserModel $model): UserEntity
    {
        return new UserEntity(
            id:        $model->id,
            name:      $model->name,
            phone:     $model->phone,
            createdAt: $model->created_at?->toDateTimeImmutable(),
        );
    }
}
