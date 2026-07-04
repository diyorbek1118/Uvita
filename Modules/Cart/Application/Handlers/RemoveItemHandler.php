<?php

declare(strict_types=1);

namespace Modules\Cart\Application\Handlers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Cart\Application\Commands\RemoveItemCommand;
use Modules\Cart\Domain\Repositories\CartRepositoryInterface;
use Modules\Cart\Infrastructure\Persistence\Models\CartModel;

final class RemoveItemHandler
{
    public function __construct(
        private readonly CartRepositoryInterface $cartRepository,
    ) {}

    public function handle(RemoveItemCommand $command): CartModel
    {
        $cart = $this->cartRepository->findByUserId($command->userId);

        if ($cart === null) {
            throw new ModelNotFoundException('Savatcha topilmadi.');
        }

        $cart->removeItem($command->dto->productId);

        $this->cartRepository->save($cart);

        return CartModel::with('items.product.category')
            ->where('user_id', $command->userId)
            ->firstOrFail();
    }
}
