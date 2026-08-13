<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Handlers;

use Modules\Admin\Application\Queries\GetStaffByIdQuery;
use Modules\Admin\Domain\Enums\StaffRole;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;

final class GetStaffByIdHandler
{
    public function handle(GetStaffByIdQuery $query): Staff
    {
        $staff = Staff::findOrFail($query->staffId);
        $actor = auth('sanctum')->user();

        if ($actor instanceof Staff
            && $actor->role === StaffRole::ADMIN
            && ! in_array($staff->role, [StaffRole::SELLER, StaffRole::MANAGER, StaffRole::COURIER], true)) {
            abort(403, "Admin faqat sotuvchi, menejer yoki kuryerni ko'ra oladi");
        }

        return $staff;
    }
}
