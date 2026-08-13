<?php

declare(strict_types=1);

namespace Modules\Deal\Domain\Repositories;

use Illuminate\Contracts\Pagination\Paginator;
use Modules\Deal\Domain\Entities\Deal;

interface DealRepositoryInterface
{
    public function findById(int $id): ?Deal;

    public function save(Deal $deal): int;

    /** Kiruvchi: sotuvchi sifatida olingan bitimlar */
    public function paginateIncoming(int $sellerId, int $perPage = 20): Paginator;

    /** Chiquvchi: xaridor sifatida yuborilgan bitimlar */
    public function paginateOutgoing(int $buyerId, int $perPage = 20): Paginator;
}
