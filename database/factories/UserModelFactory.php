<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\User\Infrastructure\Persistence\Models\User>
 */
class UserModelFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'phone' => '+998' . $this->faker->numberBetween(900000000, 999999999),
            'remember_token' => Str::random(10),
        ];
    }
}
