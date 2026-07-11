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

        // Admin faqat menejer/kuryerni bloklashi/faollashtira oladi.
        $actor = auth('sanctum')->user();
        if ($actor instanceof Staff
            && $actor->role === StaffRole::ADMIN
            && !in_array($staff->role, [StaffRole::MANAGER, StaffRole::COURIER], true)) {
            abort(403, "Admin faqat menejer yoki kuryerni boshqarishi mumkin");
        }

        $staff->update(['is_active' => ! $staff->is_active]);

        return $staff->fresh();
    }
}
