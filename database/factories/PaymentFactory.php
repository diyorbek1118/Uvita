<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;
use Modules\Payment\Domain\Enums\PaymentProvider;
use Modules\Payment\Domain\Enums\PaymentStatus;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Payment\Infrastructure\Persistence\Models\PaymentModel>
 */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => OrderModel::factory(),
            'provider' => $this->faker->randomElement(['payme', 'click', 'uzum']),
            'transaction_id' => 'tx_' . $this->faker->uuid(),
            'provider_transaction_id' => $this->faker->uuid(),
            'amount' => $this->faker->numberBetween(50000, 500000),
            'status' => $this->faker->randomElement(['pending', 'paid', 'failed', 'cancelled']),
            'payload' => [
                'raw' => 'webhook data',
            ],
        ];
    }
}
