<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Handlers;

use Illuminate\Support\Facades\DB;
use Modules\Admin\Application\Commands\UpdateSettingCommand;
use Modules\Admin\Application\Commands\UpdateSettingsCommand;

final class UpdateSettingsHandler
{
    public function __construct(
        private readonly UpdateSettingHandler $updateOne,
    ) {}

    public function handle(UpdateSettingsCommand $command): void
    {
        DB::transaction(function () use ($command): void {
            foreach ($command->dtos as $dto) {
                $this->updateOne->handle(new UpdateSettingCommand($dto));
            }
        });
    }
}
