<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;
use Modules\Payment\Infrastructure\Persistence\Models\PaymentModel;

/**
 * To'lovlar — buyurtma statusiga mos.
 *  paid+ statuslar  → paid to'lov (amount = grand_total)
 *  pending          → pending to'lov (ba'zilari)
 *  cancelled        → cancelled to'lov
 */
class PaymentSeeder extends Seeder
{
    private const PAID_STATUSES = ['paid', 'confirmed', 'ready_to_deliver', 'delivering', 'delivered', 'delivery_issue'];

    public function run(): void
    {
        foreach (OrderModel::all() as $order) {
            $status = $order->status->value;

            $paymentStatus = match (true) {
                in_array($status, self::PAID_STATUSES, true) => 'paid',
                $status === 'cancelled'                      => 'cancelled',
                default                                      => 'pending',   // pending
            };

            // Pending buyurtmalarning bir qismigagina to'lov yozamiz
            if ($paymentStatus === 'pending' && fake()->boolean(50)) {
                continue;
            }

            PaymentModel::create([
                'order_id'                => $order->id,
                'provider'                => fake()->randomElement(['payme', 'click', 'uzum']),
                'transaction_id'          => 'tx_' . Str::uuid(),
                'provider_transaction_id' => (string) Str::uuid(),
                'amount'                  => $order->grand_total,
                'status'                  => $paymentStatus,
                'payload'                 => ['seeded' => true],
            ]);
        }
    }
}
