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

        if ($query->role !== null) {
            $builder->where('role', StaffRole::from($query->role));
        }

        return $builder->paginate(20);
    }
}
