<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Cart\Infrastructure\Persistence\Models\CartModel;
use Modules\Product\Infrastructure\Persistence\Models\Product as ProductModel;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Cart\Infrastructure\Persistence\Models\CartItemModel>
 */
class CartItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'cart_id' => CartModel::factory(),
            'product_id' => ProductModel::factory(),
            'quantity' => $this->faker->numberBetween(1, 5),
        ];
    }
}
