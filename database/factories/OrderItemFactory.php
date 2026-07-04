<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;
use Modules\Product\Infrastructure\Persistence\Models\Product as ProductModel;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Order\Infrastructure\Persistence\Models\OrderItemModel>
 */
class OrderItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => OrderModel::factory(),
            'product_id' => ProductModel::factory(),
            'quantity' => $this->faker->numberBetween(1, 5),
            'price' => $this->faker->numberBetween(10000, 500000),
        ];
    }
}
