<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Cart\Infrastructure\Persistence\Models\CartItemModel;
use Modules\Cart\Infrastructure\Persistence\Models\CartModel;
use Modules\Product\Infrastructure\Persistence\Models\Product;
use Modules\User\Infrastructure\Persistence\Models\User;

final class CartSeeder extends Seeder
{
    public function run(): void
    {
        foreach (User::all() as $user) {
            CartModel::create(['user_id' => $user->id]);
        }

        $madina = User::where('phone', '+998902222222')->firstOrFail();
        $cart = CartModel::where('user_id', $madina->id)->firstOrFail();
        foreach (['Parkent qizil olmasi' => 5, 'Issiqxona pomidori' => 5] as $name => $quantity) {
            CartItemModel::create(['cart_id' => $cart->id, 'product_id' => Product::where('name', $name)->value('id'), 'quantity' => $quantity]);
        }
    }
}
