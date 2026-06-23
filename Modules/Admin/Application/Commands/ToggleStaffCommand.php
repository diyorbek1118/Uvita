<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Commands;

final readonly class ToggleStaffCommand
{
    public function __construct(
        public int $staffId,
    ) {}
}
