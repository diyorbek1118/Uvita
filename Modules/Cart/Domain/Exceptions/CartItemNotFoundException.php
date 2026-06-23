<?php

declare(strict_types=1);

namespace Modules\Cart\Domain\Exceptions;

use App\Shared\Exceptions\DomainException;

final class CartItemNotFoundException extends DomainException {}
