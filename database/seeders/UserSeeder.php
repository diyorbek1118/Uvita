<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\User\Infrastructure\Persistence\Models\User;

/**
 * Mijozlar (customer). 20 ta test foydalanuvchi.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 0; $i < 20; $i++) {
            User::create([
                'name'           => fake()->name(),
                'phone'          => '+998' . fake()->numberBetween(900000000, 999999999),
                'remember_token' => Str::random(10),
            ]);
        }
    }
}
