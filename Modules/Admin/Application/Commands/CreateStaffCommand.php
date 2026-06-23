<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Commands;

use Modules\Admin\Application\DTOs\CreateStaffDTO;

final readonly class CreateStaffCommand
{
    public function __construct(
        public CreateStaffDTO $dto,
    ) {}
}
