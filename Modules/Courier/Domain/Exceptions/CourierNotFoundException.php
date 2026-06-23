<?php

declare(strict_types=1);

namespace Modules\Courier\Domain\Exceptions;

use App\Shared\Exceptions\DomainException;

final class CourierNotFoundException extends DomainException {}
