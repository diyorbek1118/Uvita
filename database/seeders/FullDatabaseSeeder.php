<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Modules\Category\Infrastructure\Persistence\Models\Category;
use Modules\Product\Infrastructure\Persistence\Models\Product;
use Modules\User\Infrastructure\Persistence\Models\User;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;
use Modules\Order\Infrastructure\Persistence\Models\OrderItemModel;
use Modules\Cart\Infrastructure\Persistence\Models\CartModel;
use Modules\Cart\Infrastructure\Persistence\Models\CartItemModel;
use Modules\Payment\Infrastructure\Persistence\Models\PaymentModel;
use Modules\Review\Infrastructure\Persistence\Models\ReviewModel;
use Modules\Admin\Infrastructure\Persistence\Models\SettingModel;

class FullDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $faker = \Faker\Factory::create();

        // Truncate all tables
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        foreach (['reviews', 'payments', 'order_items', 'orders', 'cart_items', 'carts', 'products', 'categories', 'users', 'staff', 'settings', 'personal_access_tokens'] as $table) {
            DB::table($table)->truncate();
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 1. Settings
        $this->command->info('Settings yaratilmoqda...');
        SettingModel::insert([
            ['key' => 'delivery_price', 'value' => '15000', 'description' => 'Yetkazish narxi (so\'mda)'],
            ['key' => 'delivery_city', 'value' => 'Toshkent', 'description' => 'Yetkazish amalga oshiriladigan shahar'],
            ['key' => 'min_order_amount', 'value' => '50000', 'description' => 'Minimal buyurtma summasi (so\'mda)'],
            ['key' => 'otp_expiry_seconds', 'value' => '120', 'description' => 'OTP amal qilish vaqti (soniyada)'],
            ['key' => 'otp_max_attempts', 'value' => '5', 'description' => 'OTP maksimal urinishlar soni'],
            ['key' => 'otp_block_minutes', 'value' => '10', 'description' => 'OTP blok vaqti (daqiqada)'],
            ['key' => 'max_not_found_attempts', 'value' => '3', 'description' => 'Kuryer topilmadi maksimal urinish'],
            ['key' => 'review_request_delay_hours', 'value' => '24', 'description' => 'Yetkazilgandan so\'ng review so\'rash (soatda)'],
        ]);

        // 2. Staff
        $this->command->info('Staff yaratilmoqda...');
        Staff::insert([
            ['name' => 'Abdulaziz Manager', 'email' => 'manager@uvita.uz', 'password' => bcrypt('password123'), 'role' => 'manager', 'is_active' => true],
            ['name' => 'Bobur Courier', 'email' => 'courier@uvita.uz', 'password' => bcrypt('password123'), 'role' => 'courier', 'is_active' => true],
            ['name' => 'Kamola Admin', 'email' => 'admin@uvita.uz', 'password' => bcrypt('password123'), 'role' => 'admin', 'is_active' => true],
            ['name' => 'Sardor SuperAdmin', 'email' => 'super@uvita.uz', 'password' => bcrypt('password123'), 'role' => 'super_admin', 'is_active' => true],
        ]);

        // 3. Categories
        $this->command->info('Categories yaratilmoqda...');
        Category::insert([
            ['name' => 'Pizza', 'slug' => 'pizza', 'image' => null, 'parent_id' => null, 'is_active' => true],
            ['name' => 'Burgers', 'slug' => 'burgers', 'image' => null, 'parent_id' => null, 'is_active' => true],
            ['name' => 'Sushi', 'slug' => 'sushi', 'image' => null, 'parent_id' => null, 'is_active' => true],
            ['name' => 'Pasta', 'slug' => 'pasta', 'image' => null, 'parent_id' => null, 'is_active' => true],
            ['name' => 'Desert', 'slug' => 'desert', 'image' => null, 'parent_id' => null, 'is_active' => true],
            ['name' => 'Ichimliklar', 'slug' => 'ichimliklar', 'image' => null, 'parent_id' => null, 'is_active' => true],
        ]);

        // 4. Users
        $this->command->info('Users yaratilmoqda...');
        $users = [];
        for ($i = 0; $i < 20; $i++) {
            $users[] = User::create([
                'name' => $faker->name(),
                'phone' => '+998' . $faker->numberBetween(900000000, 999999999),
                'remember_token' => \Illuminate\Support\Str::random(10),
            ]);
        }

        // 5. Products
        $this->command->info('Products yaratilmoqda...');
        $products = [];
        $categories = Category::pluck('id')->toArray();
        for ($i = 1; $i <= 50; $i++) {
            $name = $faker->words(2, true);
            $products[] = Product::create([
                'name' => ucfirst($name),
                'slug' => \Illuminate\Support\Str::slug($name . '-' . $i),
                'description' => $faker->paragraph(3),
                'price' => $faker->numberBetween(10000, 500000),
                'stock' => $faker->numberBetween(5, 100),
                'status' => 'active',
                'images' => [
                    'https://picsum.photos/seed/food' . $i . '/400/300',
                    'https://picsum.photos/seed/food' . $i . 'b/400/300',
                ],
                'category_id' => $faker->randomElement($categories),
                'manager_id' => null,
                'rejection_reason' => null,
            ]);
        }

        // 6. Carts
        $this->command->info('Carts yaratilmoqda...');
        $carts = [];
        foreach ($users as $user) {
            $carts[] = CartModel::create(['user_id' => $user->id]);
        }

        // 7. Cart Items
        $this->command->info('Cart Items yaratilmoqda...');
        foreach ($carts as $cart) {
            $productIds = Product::inRandomOrder()->limit(3)->pluck('id')->toArray();
            foreach ($productIds as $productId) {
                CartItemModel::create([
                    'cart_id' => $cart->id,
                    'product_id' => $productId,
                    'quantity' => $faker->numberBetween(1, 5),
                ]);
            }
        }

        // 8. Orders
        $this->command->info('Orders yaratilmoqda...');
        $orders = [];
        foreach ($users as $user) {
            $orders[] = OrderModel::create([
                'user_id' => $user->id,
                'courier_id' => null,
                'status' => $faker->randomElement(['pending', 'paid', 'confirmed', 'delivering', 'delivered', 'cancelled']),
                'address' => [
                    'region' => $faker->randomElement(['Toshkent', 'Samarqand', 'Buxoro']),
                    'district' => $faker->city(),
                    'street' => $faker->streetName(),
                    'house' => $faker->buildingNumber(),
                ],
                'phone' => '+998' . $faker->numberBetween(900000000, 999999999),
                'phone_secondary' => null,
                'delivery_time' => $faker->dateTimeBetween('+1 day', '+7 days')->format('Y-m-d H:i'),
                'courier_note' => $faker->optional()->sentence(),
                'delivery_price' => 15000,
                'total_price' => $faker->numberBetween(50000, 500000),
                'grand_total' => $faker->numberBetween(65000, 515000),
                'not_found_count' => 0,
            ]);
        }

        // 9. Order Items
        $this->command->info('Order Items yaratilmoqda...');
        foreach ($orders as $order) {
            $productIds = Product::inRandomOrder()->limit(2)->pluck('id')->toArray();
            foreach ($productIds as $productId) {
                OrderItemModel::create([
                    'order_id' => $order->id,
                    'product_id' => $productId,
                    'quantity' => $faker->numberBetween(1, 5),
                    'price' => $faker->numberBetween(10000, 500000),
                ]);
            }
        }

        // 10. Payments
        $this->command->info('Payments yaratilmoqda...');
        foreach ($orders as $order) {
            if ($faker->boolean(70)) {
                PaymentModel::create([
                    'order_id' => $order->id,
                    'provider' => $faker->randomElement(['payme', 'click', 'uzum']),
                    'transaction_id' => 'tx_' . $faker->uuid(),
                    'provider_transaction_id' => $faker->uuid(),
                    'amount' => $order->grand_total,
                    'status' => $faker->randomElement(['pending', 'paid', 'failed']),
                    'payload' => ['raw' => 'webhook data'],
                ]);
            }
        }

        // 11. Reviews
        $this->command->info('Reviews yaratilmoqda...');
        foreach ($orders as $order) {
            if ($faker->boolean(60)) {
                $productId = OrderItemModel::where('order_id', $order->id)->value('product_id')
                    ?? Product::inRandomOrder()->value('id');
                ReviewModel::create([
                    'order_id' => $order->id,
                    'user_id' => $order->user_id,
                    'product_id' => $productId,
                    'rating' => $faker->numberBetween(3, 5),
                    'comment' => $faker->paragraph(),
                    'status' => 'approved',
                    'is_visible' => true,
                    'admin_note' => null,
                ]);
            }
        }

        $this->command->info('✅ Barcha ma\'lumotlar muvaffaqiyatli yaratildi!');
        $this->command->info('  - Products: ' . Product::count() . ' ta (picsum.photos rasmlari bilan)');
        $this->command->info('  - Users: ' . User::count());
        $this->command->info('  - Orders: ' . OrderModel::count());
    }
}
