<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\Exceptions;

use App\Shared\Exceptions\DomainException;

final class PaymentNotFoundException extends DomainException {}
