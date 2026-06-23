<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Handlers;

use Illuminate\Support\Facades\DB;

final class GetReviewStatsHandler
{
    public function handle(): array
    {
        $stats = DB::table('reviews')
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'pending'  => (int) ($stats['pending']  ?? 0),
            'approved' => (int) ($stats['approved'] ?? 0),
            'rejected' => (int) ($stats['rejected'] ?? 0),
            'total'    => (int) array_sum($stats),
        ];
    }
}
