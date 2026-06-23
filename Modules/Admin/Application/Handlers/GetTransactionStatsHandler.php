<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Handlers;

use Illuminate\Support\Facades\DB;

final class GetTransactionStatsHandler
{
    public function handle(): array
    {
        $totals    = DB::table('payments')->selectRaw('SUM(amount) as total_amount, COUNT(*) as total_count')->first();
        $paid      = DB::table('payments')->where('status', 'paid')->selectRaw('SUM(amount) as paid_amount, COUNT(*) as paid_count')->first();
        $failed    = DB::table('payments')->where('status', 'failed')->count();
        $cancelled = DB::table('payments')->where('status', 'cancelled')->count();

        $byProviderRaw = DB::table('payments')
            ->where('status', 'paid')
            ->selectRaw('provider, COUNT(*) as count, SUM(amount) as amount')
            ->groupBy('provider')
            ->get()
            ->keyBy('provider');

        $byProvider = [];
        foreach (['payme', 'click', 'uzum'] as $provider) {
            $row = $byProviderRaw->get($provider);
            $byProvider[$provider] = [
                'count'  => (int) ($row?->count  ?? 0),
                'amount' => (int) ($row?->amount ?? 0),
            ];
        }

        return [
            'total_amount'    => (int) ($totals?->total_amount ?? 0),
            'total_count'     => (int) ($totals?->total_count  ?? 0),
            'paid_amount'     => (int) ($paid?->paid_amount    ?? 0),
            'paid_count'      => (int) ($paid?->paid_count     ?? 0),
            'failed_count'    => $failed,
            'cancelled_count' => $cancelled,
            'by_provider'     => $byProvider,
        ];
    }
}
