<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Handlers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Admin\Application\Queries\GetAllStaffQuery;
use Modules\Admin\Domain\Enums\StaffRole;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;

final class GetAllStaffHandler
{
    public function handle(GetAllStaffQuery $query): LengthAwarePaginator
    {
        $builder = Staff::query()->latest();

        $actor = auth('sanctum')->user();
        if ($actor instanceof Staff && $actor->role === StaffRole::ADMIN) {
            $builder->whereIn('role', [StaffRole::SELLER, StaffRole::MANAGER, StaffRole::COURIER]);
        }

        if ($query->role !== null) {
            $role = StaffRole::tryFrom($query->role);
            if ($role === null) {
                abort(422, "Noto'g'ri xodim roli");
            }

            $builder->where('role', $role);
        }

        return $builder->paginate(20);
    }
}
