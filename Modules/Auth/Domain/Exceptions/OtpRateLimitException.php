<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Exceptions;

use App\Shared\Exceptions\DomainException;
use DateTimeImmutable;

final class OtpRateLimitException extends DomainException
{
    public function __construct(DateTimeImmutable $blockedUntil)
    {
        $minutesLeft = (int) ceil(
            ($blockedUntil->getTimestamp() - (new DateTimeImmutable())->getTimestamp()) / 60
        );

        parent::__construct(
            "Juda ko'p urinish. {$minutesLeft} daqiqadan so'ng qayta urinib ko'ring."
        );
    }
}
