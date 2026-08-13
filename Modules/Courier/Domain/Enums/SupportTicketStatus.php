<?php

declare(strict_types=1);

namespace Modules\Courier\Domain\Enums;

enum SupportTicketStatus: string
{
    case OPEN = 'open';
    case IN_PROGRESS = 'in_progress';
    case RESOLVED = 'resolved';
}
