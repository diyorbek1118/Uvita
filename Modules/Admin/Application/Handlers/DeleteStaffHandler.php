<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Handlers;

use Modules\Admin\Application\Commands\DeleteStaffCommand;
use Modules\Admin\Domain\Enums\StaffRole;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;

final class DeleteStaffHandler
{
    public function handle(DeleteStaffCommand $command): void
    {
        $staff = Staff::findOrFail($command->staffId);

        if ($staff->role === StaffRole::SUPER_ADMIN) {
            abort(422, "Bosh adminni o'chirib bo'lmaydi");
        }

        $staff->delete();
    }
}
