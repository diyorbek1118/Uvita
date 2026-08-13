<?php

declare(strict_types=1);

namespace Modules\Courier\Domain\Enums;

enum CourierPayoutStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case CANCELLED = 'cancelled';
}
