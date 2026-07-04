<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\User\Infrastructure\Persistence\Models\User as UserModel;
use Modules\Product\Infrastructure\Persistence\Models\Product as ProductModel;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Order\Infrastructure\Persistence\Models\OrderModel>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => UserModel::factory(),
            'courier_id' => null,
            'status' => $this->faker->randomElement(['pending', 'paid', 'confirmed', 'ready_to_deliver', 'delivering', 'delivered', 'cancelled', 'delivery_issue']),
            'address' => [
                'region' => $this->faker->randomElement(['Toshkent', 'Samarqand', 'Buxoro', 'Navoiy', 'Qashqadaryo']),
                'district' => $this->faker->city(),
                'street' => $this->faker->streetName(),
                'house' => $this->faker->buildingNumber(),
                'landmark' => $this->faker->optional()->sentence(),
            ],
            'phone' => '+998' . $this->faker->numberBetween(900000000, 999999999),
            'phone_secondary' => $this->faker->optional()->phoneNumber(),
            'delivery_time' => $this->faker->dateTimeBetween('+1 day', '+7 days')->format('Y-m-d H:i'),
            'courier_note' => $this->faker->optional()->sentence(),
            'delivery_price' => 15000,
            'total_price' => $this->faker->numberBetween(50000, 500000),
            'grand_total' => $this->faker->numberBetween(65000, 515000),
            'not_found_count' => 0,
        ];
    }
}
