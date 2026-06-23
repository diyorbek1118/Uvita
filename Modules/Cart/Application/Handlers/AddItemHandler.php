<?php

declare(strict_types=1);

namespace Modules\Cart\Application\Handlers;

use App\Shared\Exceptions\DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Cart\Application\Commands\AddItemCommand;
use Modules\Cart\Domain\Entities\Cart;
use Modules\Cart\Domain\Entities\CartItem;
use Modules\Cart\Domain\Exceptions\InsufficientStockException;
use Modules\Cart\Domain\Repositories\CartRepositoryInterface;
use Modules\Cart\Infrastructure\Persistence\Models\CartModel;
use Modules\Product\Domain\Enums\ProductStatusEnum;
use Modules\Product\Domain\Repositories\ProductRepositoryInterface;

final class AddItemHandler
{
    public function __construct(
        private readonly CartRepositoryInterface    $cartRepository,
        private readonly ProductRepositoryInterface $productRepository,
    ) {}

    public function handle(AddItemCommand $command): CartModel
    {
        $product = $this->productRepository->findById($command->dto->productId);

        if ($product === null) {
            throw new ModelNotFoundException('Mahsulot topilmadi.');
        }

        if ($product->status !== ProductStatusEnum::Active) {
            throw new DomainException('Bu mahsulot hozir mavjud emas.');
        }

        if ($product->stock === 0) {
            throw new InsufficientStockException('Mahsulot tugagan.');
        }

        $cart = $this->cartRepository->findByUserId($command->userId)
            ?? new Cart(id: null, userId: $command->userId);

        $cartItem = new CartItem(
            id:        null,
            cartId:    $cart->id,
            productId: $command->dto->productId,
            quantity:  $command->dto->quantity,
            price:     $product->price,
        );

        $cart->addItem($cartItem, $product->stock);

        $this->cartRepository->save($cart);

        return CartModel::with('items.product')
            ->where('user_id', $command->userId)
            ->firstOrFail();
    }
}
