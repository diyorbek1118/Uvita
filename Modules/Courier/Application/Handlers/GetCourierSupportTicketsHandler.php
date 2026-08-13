<?php

declare(strict_types=1);

namespace Modules\Courier\Application\Handlers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Courier\Infrastructure\Persistence\Models\CourierSupportTicket;

final class GetCourierSupportTicketsHandler
{
    public function handle(int $courierId): LengthAwarePaginator
    {
        return CourierSupportTicket::query()->where('courier_id', $courierId)->latest()->paginate(20);
    }
}
