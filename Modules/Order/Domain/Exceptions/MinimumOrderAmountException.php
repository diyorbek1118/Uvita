<?php

declare(strict_types=1);

namespace Modules\Order\Domain\Exceptions;

use App\Shared\Exceptions\DomainException;

/**
 * Mahsulotlar summasi minimal buyurtmadan (masalan 50 000 so'm) kam bo'lganda.
 * Global handler DomainException'ni 422 ga map qiladi.
 */
final class MinimumOrderAmountException extends DomainException {}
