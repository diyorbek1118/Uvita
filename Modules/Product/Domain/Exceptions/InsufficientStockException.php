<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Exceptions;

use App\Shared\Exceptions\DomainException;

final class InsufficientStockException extends DomainException {}
