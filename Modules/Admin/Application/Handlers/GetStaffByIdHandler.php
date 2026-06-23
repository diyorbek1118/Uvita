<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Handlers;

use Modules\Admin\Application\Queries\GetStaffByIdQuery;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;

final class GetStaffByIdHandler
{
    public function handle(GetStaffByIdQuery $query): Staff
    {
        return Staff::findOrFail($query->staffId);
    }
}
