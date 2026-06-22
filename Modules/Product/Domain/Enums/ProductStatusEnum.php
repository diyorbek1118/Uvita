<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Enums;

enum ProductStatusEnum: string
{
    case Active   = 'active';
    case Inactive = 'inactive';
    case Rejected = 'rejected';
}
