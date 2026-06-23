<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Handlers;

use Modules\Admin\Application\Commands\ToggleCourierActiveCommand;
use Modules\Admin\Domain\Enums\StaffRole;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;

final class ToggleCourierActiveHandler
{
    public function handle(ToggleCourierActiveCommand $command): Staff
    {
        $courier = Staff::where('role', StaffRole::COURIER)->findOrFail($command->id);

        $courier->update(['is_active' => ! $courier->is_active]);

        return $courier->fresh();
    }
}
