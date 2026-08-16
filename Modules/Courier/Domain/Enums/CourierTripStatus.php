<?php

declare(strict_types=1);

namespace Modules\Courier\Domain\Enums;

enum CourierTripStatus: string
{
    case PICKING_UP = 'picking_up';
    case DELIVERING = 'delivering';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
