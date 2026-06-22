<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Exceptions;

use App\Shared\Exceptions\DomainException;

final class InvalidOtpException extends DomainException
{
    public function __construct(string $message = "OTP kod noto'g'ri yoki muddati o'tgan.")
    {
        parent::__construct($message);
    }
}
