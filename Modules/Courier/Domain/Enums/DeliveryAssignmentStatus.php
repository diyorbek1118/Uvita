<?php

declare(strict_types=1);

namespace Modules\Courier\Domain\Enums;

enum DeliveryAssignmentStatus: string
{
    case ASSIGNED = 'assigned';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';
}
