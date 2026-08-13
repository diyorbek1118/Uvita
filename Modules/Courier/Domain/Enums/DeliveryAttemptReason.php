<?php

declare(strict_types=1);

namespace Modules\Courier\Domain\Enums;

enum DeliveryAttemptReason: string
{
    case NO_ANSWER = 'no_answer';
    case WRONG_ADDRESS = 'wrong_address';
    case CUSTOMER_UNAVAILABLE = 'customer_unavailable';
    case OTHER = 'other';
}
