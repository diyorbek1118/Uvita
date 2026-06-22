<?php

declare(strict_types=1);

namespace Modules\User\Domain\Repositories;

use Modules\User\Domain\Entities\User;

interface UserRepositoryInterface
{
    public function findById(int $id): ?User;

    public function findByPhone(string $phone): ?User;

    public function save(User $user): User;
}
