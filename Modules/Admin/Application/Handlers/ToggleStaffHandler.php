<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Handlers;

use Modules\Admin\Application\Commands\ToggleStaffCommand;
use Modules\Admin\Domain\Enums\StaffRole;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;

final class ToggleStaffHandler
{
    public function handle(ToggleStaffCommand $command): Staff
    {
        $staff = Staff::findOrFail($command->staffId);

        if ($staff->role === StaffRole::SUPER_ADMIN) {
            abort(422, 'Bosh adminni bloklash mumkin emas');
        }

        $staff->update(['is_active' => ! $staff->is_active]);

        return $staff->fresh();
    }
}
