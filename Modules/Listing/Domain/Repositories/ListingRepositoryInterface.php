<?php

declare(strict_types=1);

namespace Modules\Listing\Domain\Repositories;

use Illuminate\Contracts\Pagination\Paginator;
use Modules\Listing\Domain\Entities\Listing;

interface ListingRepositoryInterface
{
    public function findById(int $id): ?Listing;

    public function save(Listing $listing): int;

    public function delete(int $id): void;

    /** Faol e'lonlar (moderatsiyadan o'tgan) — filtr va pagination bilan */
    public function paginateActive(array $filters = [], int $perPage = 20): Paginator;

    /** Sotuvchining o'z e'lonlari */
    public function paginateBySeller(int $sellerId, int $perPage = 20): Paginator;
}
