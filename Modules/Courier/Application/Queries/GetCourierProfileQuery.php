<?php

declare(strict_types=1);

namespace Modules\Courier\Application\Queries;

final readonly class GetCourierProfileQuery
{
    public function __construct(public int $courierId) {}
}
