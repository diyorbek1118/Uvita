<?php

declare(strict_types=1);

namespace Modules\Deal\Application\Handlers;

use Illuminate\Contracts\Pagination\Paginator;
use Modules\Deal\Application\Queries\GetIncomingDealsQuery;
use Modules\Deal\Domain\Repositories\DealRepositoryInterface;

final class GetIncomingDealsHandler
{
    public function __construct(
        private readonly DealRepositoryInterface $deals,
    ) {}

    public function handle(GetIncomingDealsQuery $query): Paginator
    {
        return $this->deals->paginateIncoming($query->sellerId, $query->perPage);
    }
}
