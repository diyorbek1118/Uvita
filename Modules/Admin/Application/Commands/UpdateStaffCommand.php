<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Commands;

use Modules\Admin\Application\DTOs\UpdateStaffDTO;

final readonly class UpdateStaffCommand
{
    public function __construct(
        public int           $staffId,
        public UpdateStaffDTO $dto,
    ) {}
}
