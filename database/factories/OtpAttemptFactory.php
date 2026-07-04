<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Auth\Infrastructure\Persistence\Models\OtpAttempt>
 */
class OtpAttemptFactory extends Factory
{
    public function definition(): array
    {
        return [
            'phone' => '+998' . $this->faker->numberBetween(900000000, 999999999),
            'code' => $this->faker->numerify('######'),
            'type' => $this->faker->randomElement(['login', 'register']),
            'attempts_count' => $this->faker->numberBetween(0, 5),
            'blocked_until' => null,
            'expires_at' => now()->addMinutes(5),
            'is_verified' => false,
        ];
    }
}
