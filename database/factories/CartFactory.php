<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\User\Infrastructure\Persistence\Models\User as UserModel;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Cart\Infrastructure\Persistence\Models\CartModel>
 */
class CartFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => UserModel::factory(),
        ];
    }
}
