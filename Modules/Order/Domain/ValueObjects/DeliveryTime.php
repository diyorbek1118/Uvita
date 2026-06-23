<?php

declare(strict_types=1);

namespace Modules\Order\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class DeliveryTime
{
    public function __construct(public string $value)
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException("Yetkazib berish vaqti bo'sh bo'lishi mumkin emas.");
        }
    }
}
