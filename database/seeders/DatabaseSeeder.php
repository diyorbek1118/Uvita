<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seed dirijyori — jadvallarni tozalaydi va seederlarni tartib bilan chaqiradi.
 * Har bir jadval uchun alohida seeder (SettingsSeeder, StaffSeeder, ...).
 */
class DatabaseSeeder extends Seeder
{
    private const TABLES = [
        'reviews', 'payments', 'order_items', 'orders',
        'cart_items', 'carts', 'products', 'categories',
        'users', 'staff', 'settings', 'personal_access_tokens',
    ];

    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach (self::TABLES as $table) {
            DB::table($table)->truncate();
        }
        Schema::enableForeignKeyConstraints();

        $this->call([
            SettingsSeeder::class,
            StaffSeeder::class,
            CategorySeeder::class,
            UserSeeder::class,
            ProductSeeder::class,
            CartSeeder::class,
            OrderSeeder::class,
            PaymentSeeder::class,
            ReviewSeeder::class,
        ]);

        $this->command->info('✅ Seed tayyor: '
            . DB::table('products')->count() . ' mahsulot, '
            . DB::table('users')->count() . ' mijoz, '
            . DB::table('orders')->count() . ' buyurtma, '
            . DB::table('staff')->count() . ' xodim.');
    }
}
