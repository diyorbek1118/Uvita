<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Admin\Infrastructure\Persistence\Models\Staff>
 */
class StaffFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->email(),
            'password' => bcrypt('password123'),
            'role' => $this->faker->randomElement(['manager', 'courier', 'admin', 'super_admin']),
            'is_active' => $this->faker->boolean(),
        ];
    }
}
