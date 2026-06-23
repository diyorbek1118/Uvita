<?php

declare(strict_types=1);

namespace Modules\Review\Domain\Repositories;

use Modules\Review\Domain\Entities\Review;

interface ReviewRepositoryInterface
{
    public function findById(int $id): ?Review;

    public function findByOrderId(int $orderId): ?Review;

    public function save(Review $review): int;
}
