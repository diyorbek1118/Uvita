<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Contracts;

use Modules\User\Domain\Entities\User as UserEntity;

interface TokenServiceInterface
{
    public function createForUser(UserEntity $user): string;
}
