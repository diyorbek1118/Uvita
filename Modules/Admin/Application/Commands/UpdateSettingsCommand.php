<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Commands;

use Modules\Admin\Application\DTOs\UpdateSettingDTO;

final readonly class UpdateSettingsCommand
{
    /** @param UpdateSettingDTO[] $dtos */
    public function __construct(
        public array $dtos,
    ) {}
}
