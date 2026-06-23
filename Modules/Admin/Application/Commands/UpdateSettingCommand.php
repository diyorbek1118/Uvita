<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Commands;

use Modules\Admin\Application\DTOs\UpdateSettingDTO;

final readonly class UpdateSettingCommand
{
    public function __construct(
        public UpdateSettingDTO $dto,
    ) {}
}
