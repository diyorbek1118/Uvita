<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;
use Modules\Product\Infrastructure\Persistence\Models\Product as ProductModel;
use Modules\User\Infrastructure\Persistence\Models\User as UserModel;
use Modules\Review\Domain\Enums\ReviewStatus;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Review\Infrastructure\Persistence\Models\ReviewModel>
 */
class ReviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => OrderModel::factory(),
            'user_id' => UserModel::factory(),
            'product_id' => ProductModel::factory(),
            'rating' => $this->faker->numberBetween(1, 5),
            'comment' => $this->faker->paragraph(),
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
            'is_visible' => $this->faker->boolean(),
            'admin_note' => $this->faker->optional()->sentence(),
        ];
    }
}
