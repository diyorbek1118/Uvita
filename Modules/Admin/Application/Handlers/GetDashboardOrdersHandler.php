<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Handlers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Admin\Application\Queries\GetDashboardOrdersQuery;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;

final class GetDashboardOrdersHandler
{
    /** Manager ko'ra oladigan statuslar (pending va cancelled'siz). */
    public const MANAGER_VISIBLE = [
        'paid', 'confirmed', 'ready_to_deliver',
        'delivering', 'delivered', 'delivery_issue',
    ];

    public function handle(GetDashboardOrdersQuery $query): LengthAwarePaginator
    {
        $builder = OrderModel::query()
            ->with(['user', 'courier'])
            ->withCount('items')
            ->orderByDesc('created_at');

        if ($query->managerScope) {
            $builder->whereIn('status', self::MANAGER_VISIBLE);
        }

        if ($query->status !== null && $query->status !== '') {
            $builder->where('status', $query->status);
        }

        if ($query->search !== null && $query->search !== '') {
            $search = $query->search;
            $builder->where(function ($sub) use ($search): void {
                $sub->where('phone', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }

        if ($query->dateFrom !== null && $query->dateFrom !== '') {
            $builder->whereDate('created_at', '>=', $query->dateFrom);
        }

        if ($query->dateTo !== null && $query->dateTo !== '') {
            $builder->whereDate('created_at', '<=', $query->dateTo);
        }

        return $builder->paginate($query->perPage);
    }
}
