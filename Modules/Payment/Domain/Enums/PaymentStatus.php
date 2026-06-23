<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\Enums;

enum PaymentStatus: string
{
    case PENDING   = 'pending';
    case PAID      = 'paid';
    case FAILED    = 'failed';
    case CANCELLED = 'cancelled';
}
