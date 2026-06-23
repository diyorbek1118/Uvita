<?php

declare(strict_types=1);

namespace Modules\Order\Application\Queries;

final readonly class GetOrderByIdQuery
{
    public function __construct(
        public int $orderId,
        public int $userId,
    ) {}
}
