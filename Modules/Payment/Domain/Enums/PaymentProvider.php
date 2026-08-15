<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\Enums;

enum PaymentProvider: string
{
    case CASH = 'cash';
    case PAYME = 'payme';
    case CLICK = 'click';
    case UZUM = 'uzum';
}
