<?php

declare(strict_types=1);

namespace Modules\Deal\Application\Handlers;

use Illuminate\Contracts\Pagination\Paginator;
use Modules\Deal\Application\Queries\GetOutgoingDealsQuery;
use Modules\Deal\Domain\Repositories\DealRepositoryInterface;

final class GetOutgoingDealsHandler
{
    public function __construct(
        private readonly DealRepositoryInterface $deals,
    ) {}

    public function handle(GetOutgoingDealsQuery $query): Paginator
    {
        return $this->deals->paginateOutgoing($query->buyerId, $query->perPage);
    }
}
