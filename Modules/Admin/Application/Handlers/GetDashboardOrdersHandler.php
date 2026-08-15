<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Handlers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Admin\Application\Queries\GetDashboardOrdersQuery;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;
use Modules\Payment\Domain\Enums\PaymentProvider;

final class GetDashboardOrdersHandler
{
    /** Manager ko'ra oladigan jarayondagi statuslar. */
    public const MANAGER_VISIBLE = [
        'paid', 'confirmed', 'ready_to_deliver',
        'delivering', 'delivered', 'delivery_issue',
    ];

    public static function applyManagerVisibility(Builder $builder): Builder
    {
        return $builder->where(function (Builder $scope): void {
            $scope->whereIn('status', self::MANAGER_VISIBLE)
                ->orWhere(function (Builder $pendingCash): void {
                    $pendingCash->where('status', 'pending')
                        ->whereHas('latestPayment', fn (Builder $payment) => $payment
                            ->where('provider', PaymentProvider::CASH->value));
                });
        });
    }

    public function handle(GetDashboardOrdersQuery $query): LengthAwarePaginator
    {
        $builder = OrderModel::query()
            ->with(['user', 'courier'])
            ->withCount('items')
            ->orderByDesc('created_at');

        if ($query->managerScope) {
            self::applyManagerVisibility($builder);
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
