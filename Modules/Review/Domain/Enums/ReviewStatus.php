<?php

declare(strict_types=1);

namespace Modules\Review\Domain\Enums;

enum ReviewStatus: string
{
    case PENDING  = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
}
