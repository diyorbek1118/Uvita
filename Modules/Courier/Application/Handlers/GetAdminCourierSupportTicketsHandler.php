<?php

declare(strict_types=1);

namespace Modules\Courier\Application\Handlers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Courier\Infrastructure\Persistence\Models\CourierSupportTicket;

final class GetAdminCourierSupportTicketsHandler
{
    public function handle(?string $status): LengthAwarePaginator
    {
        return CourierSupportTicket::query()
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(20);
    }
}
