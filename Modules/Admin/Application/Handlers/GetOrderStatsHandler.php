<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Handlers;

use Illuminate\Support\Facades\DB;

final class GetOrderStatsHandler
{
    public function handle(): array
    {
        $stats = DB::table('orders')
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $statuses = [
            'pending', 'paid', 'confirmed', 'ready_to_deliver',
            'delivering', 'delivered', 'delivery_issue', 'cancelled',
        ];

        $result = [];
        foreach ($statuses as $status) {
            $result[$status] = (int) ($stats[$status] ?? 0);
        }
        $result['total'] = array_sum($result);

        return $result;
    }
}
