<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Handlers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Admin\Application\Queries\GetAllUsersQuery;
use Modules\User\Infrastructure\Persistence\Models\User;

final class GetAllUsersHandler
{
    public function handle(GetAllUsersQuery $query): LengthAwarePaginator
    {
        $builder = User::query()
            ->withCount('orders')
            ->withMax('orders', 'created_at')
            ->latest();

        if ($query->search !== null) {
            $builder->where(function ($q) use ($query): void {
                $q->where('phone', 'like', "%{$query->search}%")
                  ->orWhere('name', 'like', "%{$query->search}%");
            });
        }

        return $builder->paginate(20);
    }
}
