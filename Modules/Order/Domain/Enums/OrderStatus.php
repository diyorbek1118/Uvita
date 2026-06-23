<?php

declare(strict_types=1);

namespace Modules\Order\Domain\Enums;

enum OrderStatus: string
{
    case PENDING          = 'pending';
    case PAID             = 'paid';
    case CONFIRMED        = 'confirmed';
    case READY_TO_DELIVER = 'ready_to_deliver';
    case DELIVERING       = 'delivering';
    case DELIVERED        = 'delivered';
    case CANCELLED        = 'cancelled';
    case DELIVERY_ISSUE   = 'delivery_issue';
}
