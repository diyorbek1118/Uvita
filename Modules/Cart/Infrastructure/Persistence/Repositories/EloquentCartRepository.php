<?php

declare(strict_types=1);

namespace Modules\Cart\Infrastructure\Persistence\Repositories;

use Modules\Cart\Domain\Entities\Cart;
use Modules\Cart\Domain\Entities\CartItem;
use Modules\Cart\Domain\Repositories\CartRepositoryInterface;
use Modules\Cart\Infrastructure\Persistence\Models\CartItemModel;
use Modules\Cart\Infrastructure\Persistence\Models\CartModel;

final class EloquentCartRepository implements CartRepositoryInterface
{
    public function findByUserId(int $userId): ?Cart
    {
        $model = CartModel::with('items.product')
            ->where('user_id', $userId)
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function save(Cart $cart): void
    {
        $cartModel = CartModel::firstOrCreate(['user_id' => $cart->userId]);

        $productIds = array_map(fn(CartItem $item) => $item->productId, $cart->items);

        if (empty($productIds)) {
            $cartModel->items()->delete();
            return;
        }

        $cartModel->items()->whereNotIn('product_id', $productIds)->delete();

        foreach ($cart->items as $item) {
            $cartModel->items()->updateOrCreate(
                ['product_id' => $item->productId],
                ['quantity'   => $item->quantity],
            );
        }
    }

    public function clear(int $cartId): void
    {
        CartItemModel::where('cart_id', $cartId)->delete();
    }

    private function toDomain(CartModel $model): Cart
    {
        $items = $model->items->map(function (CartItemModel $item): CartItem {
            return new CartItem(
                id:        $item->id,
                cartId:    $item->cart_id,
                productId: $item->product_id,
                quantity:  $item->quantity,
                price:     $item->product->price,
            );
        })->all();

        return new Cart(
            id:     $model->id,
            userId: $model->user_id,
            items:  $items,
        );
    }
}
