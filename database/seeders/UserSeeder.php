<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\User\Infrastructure\Persistence\Models\User;

final class UserSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('market123');

        User::insert([
            ['name' => 'Diyorbek', 'surname' => 'Karimov', 'phone' => '+998901111111', 'password' => $password, 'region' => 'Jizzax', 'district' => 'Jizzax shahri', 'address' => 'Sh. Rashidov ko‘chasi, 18-uy', 'lat' => 40.1158, 'lng' => 67.8422, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Madina', 'surname' => 'Ismoilova', 'phone' => '+998902222222', 'password' => $password, 'region' => 'Toshkent shahri', 'district' => 'Yunusobod', 'address' => 'Amir Temur shoh ko‘chasi, 108-uy', 'lat' => 41.3661, 'lng' => 69.2880, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Akmal', 'surname' => 'Rasulov', 'phone' => '+998903333333', 'password' => $password, 'region' => 'Samarqand', 'district' => 'Samarqand shahri', 'address' => 'Mirzo Ulug‘bek ko‘chasi, 42-uy', 'lat' => 39.6542, 'lng' => 66.9597, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
