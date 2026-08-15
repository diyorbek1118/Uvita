<?php

declare(strict_types=1);

namespace Modules\Order\Application\Handlers;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Order\Application\Queries\GetPaidOrdersQuery;
use Modules\Order\Domain\Enums\OrderStatus;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;
use Modules\Payment\Domain\Enums\PaymentProvider;

final class GetPaidOrdersHandler
{
    public function handle(GetPaidOrdersQuery $query): LengthAwarePaginator
    {
        return OrderModel::with(['items.product'])
            ->where(function ($builder): void {
                $builder->where('status', OrderStatus::PAID->value)
                    ->orWhere(function ($cash): void {
                        $cash->where('status', OrderStatus::PENDING->value)
                            ->whereHas('latestPayment', fn ($payment) => $payment
                                ->where('provider', PaymentProvider::CASH->value));
                    });
            })
            ->orderByDesc('created_at')
            ->paginate(15);
    }
}
