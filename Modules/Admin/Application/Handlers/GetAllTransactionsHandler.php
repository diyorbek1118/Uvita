<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Handlers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Admin\Application\Queries\GetAllTransactionsQuery;
use Modules\Payment\Infrastructure\Persistence\Models\PaymentModel;

final class GetAllTransactionsHandler
{
    public function handle(GetAllTransactionsQuery $query): LengthAwarePaginator
    {
        $builder = PaymentModel::with('order')->latest();

        if ($query->provider !== null && $query->provider !== 'all') {
            $builder->where('provider', $query->provider);
        }

        if ($query->status !== null && $query->status !== 'all') {
            $builder->where('status', $query->status);
        }

        if ($query->dateFrom !== null) {
            $builder->whereDate('created_at', '>=', $query->dateFrom);
        }

        if ($query->dateTo !== null) {
            $builder->whereDate('created_at', '<=', $query->dateTo);
        }

        return $builder->paginate(20);
    }
}
