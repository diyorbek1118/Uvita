<?php

declare(strict_types=1);

namespace Modules\Cart\Domain\Repositories;

use Modules\Cart\Domain\Entities\Cart;

interface CartRepositoryInterface
{
    public function findByUserId(int $userId): ?Cart;
    public function save(Cart $cart): void;
    public function clear(int $cartId): void;
}
