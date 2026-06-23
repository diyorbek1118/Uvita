<?php

declare(strict_types=1);

namespace Modules\Admin\Domain\Exceptions;

use RuntimeException;

final class SettingNotFoundException extends RuntimeException
{
    public function __construct(string $key = '')
    {
        parent::__construct("Sozlama topilmadi" . ($key !== '' ? ": {$key}" : ''));
    }
}
