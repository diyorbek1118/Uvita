<?php

declare(strict_types=1);

namespace Modules\Courier\Application\Queries;

final readonly class GetCourierStatsQuery
{
    public function __construct(public int $courierId) {}
}
