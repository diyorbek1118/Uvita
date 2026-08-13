<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\User\Infrastructure\Persistence\Models\User;

/**
 * Foydalanuvchilar (customer). 24 ta test foydalanuvchi — dehqon va xaridorlar.
 */
class UserSeeder extends Seeder
{
    private const REGIONS = [
        'Toshkent', 'Toshkent vil.', 'Samarqand', 'Farg\'ona', 'Andijon',
        'Namangan', 'Buxoro', 'Xorazm', 'Jizzax', 'Qashqadaryo',
        'Surxondaryo', 'Sirdaryo', 'Navoiy', 'Nukus', 'Chirchiq', 'Angren',
    ];

    public function run(): void
    {
        for ($i = 0; $i < 24; $i++) {
            User::create([
                'name'           => fake()->name(),
                'phone'          => '+998' . fake()->numberBetween(900000000, 999999999),
                'region'         => self::REGIONS[array_rand(self::REGIONS)],
                'district'       => fake()->city(),
                'remember_token' => Str::random(10),
            ]);
        }
    }
}
