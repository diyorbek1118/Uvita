<?php

declare(strict_types=1);

namespace Modules\Order\Application\Queries;

final readonly class GetCourierOrdersQuery
{
    public function __construct(public int $courierId) {}
}
