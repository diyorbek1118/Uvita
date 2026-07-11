<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Cart\Infrastructure\Persistence\Models\CartItemModel;
use Modules\Cart\Infrastructure\Persistence\Models\CartModel;
use Modules\Product\Infrastructure\Persistence\Models\Product;
use Modules\User\Infrastructure\Persistence\Models\User;

/**
 * Ba'zi mijozlar uchun to'ldirilgan savatcha (persistent).
 */
class CartSeeder extends Seeder
{
    public function run(): void
    {
        $userIds       = User::take(10)->pluck('id')->all();
        $activeProducts = Product::where('status', 'active')->where('stock', '>', 0)->pluck('id')->all();

        if ($activeProducts === []) {
            return;
        }

        foreach ($userIds as $userId) {
            $cart = CartModel::create(['user_id' => $userId]);

            $picked = fake()->randomElements($activeProducts, fake()->numberBetween(1, 3));
            foreach ($picked as $productId) {
                CartItemModel::create([
                    'cart_id'    => $cart->id,
                    'product_id' => $productId,
                    'quantity'   => fake()->numberBetween(1, 4),
                ]);
            }
        }
    }
}
