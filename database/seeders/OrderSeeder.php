<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Shared\Services\Fee\OrderFeeCalculator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Modules\Order\Infrastructure\Persistence\Models\OrderItemModel;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;
use Modules\Product\Infrastructure\Persistence\Models\Product;
use Modules\User\Infrastructure\Persistence\Models\User;

/**
 * Buyurtmalar + order_items.
 *  - Narxlash: mijoz mahsulot + 15% xizmat haqi; kuryer haqi ichki (OrderFeeCalculator).
 *  - Har buyurtma >= 50 000 so'm (minimal buyurtma).
 *  - Status timeline milestone vaqtlari statusga qarab to'ldiriladi.
 */
class OrderSeeder extends Seeder
{
    private const MIN_ORDER = 50000;

    /** Statuslarni vaznlangan tanlash uchun havza. */
    private const STATUS_POOL = [
        'delivered', 'delivered', 'delivered', 'delivered',
        'delivering', 'delivering',
        'paid', 'paid', 'confirmed', 'ready_to_deliver',
        'pending', 'pending', 'cancelled', 'delivery_issue',
    ];

    public function run(): void
    {
        $userIds    = User::pluck('id')->all();
        $courierIds = Staff::where('role', 'courier')->pluck('id')->all();
        $products   = Product::where('status', 'active')->get(['id', 'price']);
        $calculator = new OrderFeeCalculator();

        if ($userIds === [] || $products->isEmpty()) {
            return;
        }

        for ($n = 0; $n < 25; $n++) {
            $status = fake()->randomElement(self::STATUS_POOL);

            // 1–3 mahsulot, jami >= 50 000 bo'lguncha
            [$lineItems, $goods] = $this->buildLineItems($products);

            $fin  = $calculator->calculate($goods);
            $base = Carbon::now()
                ->subDays(fake()->numberBetween(0, 29))
                ->subMinutes(fake()->numberBetween(0, 1439));

            $milestones = $this->milestones($status, $base);
            $courierId  = in_array($status, ['delivering', 'delivered', 'delivery_issue'], true)
                ? fake()->randomElement($courierIds ?: [null])
                : null;

            $order = new OrderModel([
                'user_id'         => fake()->randomElement($userIds),
                'courier_id'      => $courierId,
                'status'          => $status,
                'address'         => [
                    'region'   => fake()->randomElement(['Toshkent', 'Samarqand', 'Buxoro']),
                    'district' => fake()->city(),
                    'street'   => fake()->streetName(),
                    'house'    => (string) fake()->buildingNumber(),
                    'landmark' => fake()->optional()->sentence(),
                ],
                'phone'           => '+998' . fake()->numberBetween(900000000, 999999999),
                'phone_secondary' => null,
                'delivery_time'   => fake()->dateTimeBetween('-3 days', '+5 days')->format('Y-m-d H:i'),
                'courier_note'    => fake()->optional()->sentence(),
                'total_price'     => $goods,
                'service_fee'     => $fin->platformFeeGross,
                'courier_fee'     => $fin->courierFee,
                'grand_total'     => $fin->customerTotal,
                'not_found_count' => $status === 'delivery_issue' ? 3 : 0,
                ...$milestones,
            ]);

            // created_at/updated_at ni qo'lda o'rnatamiz (avtomatik emas)
            $latest = $base;
            foreach ($milestones as $ts) {
                if ($ts->greaterThan($latest)) {
                    $latest = $ts;
                }
            }
            $order->created_at = $base;
            $order->updated_at = $latest;
            $order->timestamps = false;
            $order->save();

            foreach ($lineItems as $li) {
                OrderItemModel::create([
                    'order_id'   => $order->id,
                    'product_id' => $li['product_id'],
                    'quantity'   => $li['quantity'],
                    'price'      => $li['price'],
                ]);
            }
        }
    }

    /** @return array{0: array<int, array{product_id:int, quantity:int, price:int}>, 1: int} */
    private function buildLineItems($products): array
    {
        $count  = fake()->numberBetween(1, min(3, $products->count()));
        $picked = $products->random($count);
        $picked = $picked instanceof \Illuminate\Support\Collection ? $picked : collect([$picked]);

        $lineItems = [];
        $goods     = 0;
        foreach ($picked as $p) {
            $qty = fake()->numberBetween(1, 3);
            $lineItems[] = ['product_id' => $p->id, 'quantity' => $qty, 'price' => (int) $p->price];
            $goods += (int) $p->price * $qty;
        }

        // Minimal buyurtma summasiga yetkazish
        while ($goods < self::MIN_ORDER) {
            $lineItems[0]['quantity']++;
            $goods += $lineItems[0]['price'];
        }

        return [$lineItems, $goods];
    }

    /**
     * Statusga mos milestone vaqtlari (created_at = $base).
     * @return array<string, Carbon>
     */
    private function milestones(string $status, Carbon $base): array
    {
        $at = fn (int $minutes) => (clone $base)->addMinutes($minutes);

        return match ($status) {
            'cancelled'        => ['cancelled_at' => $at(20)],
            'paid'             => ['paid_at' => $at(10)],
            'confirmed'        => ['paid_at' => $at(10), 'confirmed_at' => $at(60)],
            'ready_to_deliver' => ['paid_at' => $at(10), 'confirmed_at' => $at(60), 'ready_at' => $at(120)],
            'delivering'       => ['paid_at' => $at(10), 'confirmed_at' => $at(60), 'ready_at' => $at(120), 'delivering_at' => $at(180)],
            'delivered'        => ['paid_at' => $at(10), 'confirmed_at' => $at(60), 'ready_at' => $at(120), 'delivering_at' => $at(180), 'delivered_at' => $at(300)],
            'delivery_issue'   => ['paid_at' => $at(10), 'confirmed_at' => $at(60), 'ready_at' => $at(120), 'delivering_at' => $at(180), 'delivery_issue_at' => $at(240)],
            default            => [],   // pending
        };
    }
}
