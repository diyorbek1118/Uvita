<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Handlers;

use Modules\Admin\Application\Queries\GetUserByIdQuery;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;
use Modules\User\Infrastructure\Persistence\Models\User;

final class GetUserByIdHandler
{
    public function handle(GetUserByIdQuery $query): User
    {
        $user = User::withCount('orders')->findOrFail($query->userId);

        $recentOrders = OrderModel::where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get(['id', 'status', 'grand_total', 'created_at']);

        $user->setAttribute('recent_orders', $recentOrders);

        return $user;
    }
}
