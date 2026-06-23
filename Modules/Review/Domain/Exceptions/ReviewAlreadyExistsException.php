<?php

declare(strict_types=1);

namespace Modules\Review\Domain\Exceptions;

use App\Shared\Exceptions\DomainException;

final class ReviewAlreadyExistsException extends DomainException {}
