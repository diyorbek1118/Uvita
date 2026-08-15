<?php

declare(strict_types=1);

namespace Modules\Order\Application\DTOs;

use Illuminate\Support\Collection;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;

final readonly class CreatedOrdersResult
{
    /** @param Collection<int, OrderModel> $orders */
    public function __construct(public Collection $orders) {}
}
