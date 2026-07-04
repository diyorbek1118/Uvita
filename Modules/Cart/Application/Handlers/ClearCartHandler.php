<?php

declare(strict_types=1);

namespace Modules\Cart\Application\Handlers;

use Modules\Cart\Application\Commands\ClearCartCommand;
use Modules\Cart\Domain\Repositories\CartRepositoryInterface;
use Modules\Cart\Infrastructure\Persistence\Models\CartModel;

final class ClearCartHandler
{
    public function __construct(
        private readonly CartRepositoryInterface $cartRepository,
    ) {}

    public function handle(ClearCartCommand $command): ?CartModel
    {
        $cart = $this->cartRepository->findByUserId($command->userId);

        if ($cart === null) {
            return null;
        }

        $cart->clear();
        $this->cartRepository->save($cart);

        return CartModel::with('items.product.category')
            ->where('user_id', $command->userId)
            ->first();
    }
}
