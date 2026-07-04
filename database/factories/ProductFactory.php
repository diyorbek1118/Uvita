<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Category\Infrastructure\Persistence\Models\Category as CategoryModel;
use Modules\Product\Infrastructure\Persistence\Models\Product as ProductModel;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Product\Infrastructure\Persistence\Models\Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->word();
        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $this->faker->paragraph(3),
            'price' => $this->faker->numberBetween(10000, 500000),
            'stock' => $this->faker->numberBetween(0, 100),
            'status' => $this->faker->randomElement(['pending', 'active', 'rejected']),
            'images' => $this->faker->randomElements([
                $this->faker->imageUrl(400, 300, 'food', true),
                $this->faker->imageUrl(400, 300, 'food', true),
            ], $this->faker->numberBetween(1, 2)),
            'category_id' => CategoryModel::factory(),
            'manager_id' => null,
            'rejection_reason' => $this->faker->optional()->sentence(),
        ];
    }
}
