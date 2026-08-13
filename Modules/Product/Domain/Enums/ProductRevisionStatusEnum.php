<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Enums;

enum ProductRevisionStatusEnum: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
}
