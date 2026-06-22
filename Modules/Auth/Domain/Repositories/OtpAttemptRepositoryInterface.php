<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Repositories;

use Modules\Auth\Domain\Entities\OtpAttempt;

interface OtpAttemptRepositoryInterface
{
    public function findActiveByPhone(string $phone): ?OtpAttempt;

    public function save(OtpAttempt $attempt): void;

    public function deleteByPhone(string $phone): void;
}
