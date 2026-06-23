<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;

class StaffSeeder extends Seeder
{
    public function run(): void
    {
        // Eski ma'lumotlarni va tokenlarni tozalaymiz
        DB::table('personal_access_tokens')
            ->where('tokenable_type', Staff::class)
            ->delete();

        Staff::truncate();

        Staff::insert([
            [
                'name'       => 'Abdulaziz Manager',
                'email'      => 'manager@uvita.uz',
                'password'   => Hash::make('password123'),
                'role'       => 'manager',
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'       => 'Bobur Courier',
                'email'      => 'courier@uvita.uz',
                'password'   => Hash::make('password123'),
                'role'       => 'courier',
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'       => 'Kamola Admin',
                'email'      => 'admin@uvita.uz',
                'password'   => Hash::make('password123'),
                'role'       => 'admin',
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'       => 'Sardor SuperAdmin',
                'email'      => 'super@uvita.uz',
                'password'   => Hash::make('password123'),
                'role'       => 'super_admin',
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->command->info('StaffSeeder: 4 ta xodim yaratildi.');
    }
}
