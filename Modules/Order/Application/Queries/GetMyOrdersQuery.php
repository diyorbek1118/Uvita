<?php

declare(strict_types=1);

namespace Modules\Order\Application\Queries;

final readonly class GetMyOrdersQuery
{
    public function __construct(public int $userId) {}
}
