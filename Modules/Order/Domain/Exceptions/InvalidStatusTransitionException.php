<?php

declare(strict_types=1);

namespace Modules\Order\Domain\Exceptions;

use App\Shared\Exceptions\DomainException;

final class InvalidStatusTransitionException extends DomainException {}
