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

final class OrderSeeder extends Seeder
{
    /** @var array<int, array<string, mixed>> */
    private const SCENARIOS = [
        ['status' => 'pending', 'days' => 0, 'items' => ['Parkent qizil olmasi' => 5, 'Yangi hosil kartoshka' => 10], 'district' => 'Jizzax shahri', 'note' => 'Qo‘ng‘iroq qilib keyin kiring.'],
        ['status' => 'paid', 'days' => 1, 'items' => ['Devzira guruchi' => 5, 'Oq no‘xat — saralangan' => 5], 'district' => 'Yunusobod', 'note' => 'Ofis resepsheniga qoldiring.'],
        ['status' => 'confirmed', 'days' => 2, 'items' => ['Issiqxona pomidori' => 8, 'Mirzacho‘l sabzisi' => 10], 'district' => 'Samarqand shahri', 'note' => null],
        ['status' => 'ready_to_deliver', 'days' => 3, 'items' => ['Parkent qora uzumi' => 8, 'Parkent qizil olmasi' => 10], 'district' => 'Jizzax shahri', 'note' => 'Mahsulotlarni ezmasdan olib keling.'],
        ['status' => 'delivering', 'days' => 4, 'items' => ['Mirzacho‘l tarvuzi' => 25, 'Obinovvot qovuni' => 12], 'district' => 'Yunusobod', 'note' => '3-kirish, 2-qavat.'],
        ['status' => 'delivered', 'days' => 8, 'items' => ['Yangi hosil kartoshka' => 20, 'Birinchi nav oq piyoz' => 20], 'district' => 'Jizzax shahri', 'note' => null],
        ['status' => 'delivered', 'days' => 14, 'items' => ['Oziq-ovqat bug‘doyi' => 50, 'Oq no‘xat — saralangan' => 10], 'district' => 'Samarqand shahri', 'note' => 'Ombor darvozasidan kiring.'],
        ['status' => 'cancelled', 'days' => 5, 'items' => ['Parkent qizil olmasi' => 10], 'district' => 'Jizzax shahri', 'note' => 'Mijoz buyurtmani bekor qilgan.'],
        ['status' => 'delivery_issue', 'days' => 6, 'items' => ['Devzira guruchi' => 5, 'Parkent qora uzumi' => 5], 'district' => 'Yunusobod', 'note' => 'Telefon vaqtincha javob bermadi.'],
    ];

    public function run(): void
    {
        $users = User::all()->values();
        $courier = Staff::where('role', 'courier')->first();
        $products = Product::where('status', 'active')->get()->keyBy('name');
        $calculator = new OrderFeeCalculator();

        foreach (self::SCENARIOS as $index => $scenario) {
            $lines = [];
            $goods = 0;
            foreach ($scenario['items'] as $name => $quantity) {
                $product = $products->get($name);
                if (! $product) {
                    throw new \RuntimeException("OrderSeeder: mahsulot topilmadi: {$name}");
                }
                $lines[] = ['product' => $product, 'quantity' => $quantity];
                $goods += $product->price * $quantity;
            }

            $fin = $calculator->calculate($goods);
            $created = Carbon::now()->subDays($scenario['days'])->setTime(10 + ($index % 6), 20);
            $times = $this->milestones($scenario['status'], $created);
            $user = $users[$index % $users->count()];
            $isCourierStage = in_array($scenario['status'], ['delivering', 'delivered', 'delivery_issue'], true);

            $order = OrderModel::create([
                'user_id' => $user->id,
                'courier_id' => $isCourierStage ? $courier?->id : null,
                'status' => $scenario['status'],
                'address' => ['region' => $user->region, 'district' => $scenario['district'], 'street' => $user->address, 'house' => (string) (12 + $index), 'landmark' => $index % 2 ? 'Mahalla markazi yaqinida' : null],
                'lat' => $user->lat,
                'lng' => $user->lng,
                'geo_level' => 'address',
                'phone' => $user->phone,
                'phone_secondary' => null,
                'delivery_time' => $created->copy()->addDay()->setTime(14, 0)->format('Y-m-d H:i'),
                'courier_note' => $scenario['note'],
                'total_price' => $goods,
                'service_fee' => $fin->platformFeeGross,
                'courier_fee' => $fin->courierFee,
                'grand_total' => $fin->customerTotal,
                'not_found_count' => $scenario['status'] === 'delivery_issue' ? 3 : 0,
                ...$times,
            ]);
            $order->forceFill(['created_at' => $created, 'updated_at' => collect($times)->filter()->max() ?? $created])->save();

            foreach ($lines as $line) {
                OrderItemModel::create(['order_id' => $order->id, 'product_id' => $line['product']->id, 'quantity' => $line['quantity'], 'price' => $line['product']->price]);
            }
        }

        $this->command?->info('✓ 9 ta buyurtma status ssenariysi yaratildi.');
    }

    /** @return array<string, Carbon> */
    private function milestones(string $status, Carbon $base): array
    {
        $at = fn (int $minutes) => $base->copy()->addMinutes($minutes);

        return match ($status) {
            'paid' => ['paid_at' => $at(10)],
            'confirmed' => ['paid_at' => $at(10), 'confirmed_at' => $at(45)],
            'ready_to_deliver' => ['paid_at' => $at(10), 'confirmed_at' => $at(45), 'ready_at' => $at(100)],
            'delivering' => ['paid_at' => $at(10), 'confirmed_at' => $at(45), 'ready_at' => $at(100), 'delivering_at' => $at(150)],
            'delivered' => ['paid_at' => $at(10), 'confirmed_at' => $at(45), 'ready_at' => $at(100), 'delivering_at' => $at(150), 'delivered_at' => $at(260)],
            'cancelled' => ['cancelled_at' => $at(25)],
            'delivery_issue' => ['paid_at' => $at(10), 'confirmed_at' => $at(45), 'ready_at' => $at(100), 'delivering_at' => $at(150), 'delivery_issue_at' => $at(210)],
            default => [],
        };
    }
}
