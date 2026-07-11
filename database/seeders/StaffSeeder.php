<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;

/**
 * Xodimlar — har rol uchun test akkaunt. Parol hammasida: password123
 */
class StaffSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password123');

        Staff::insert([
            ['name' => 'Sardor SuperAdmin', 'email' => 'super@uvita.uz',    'password' => $password, 'role' => 'super_admin', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Kamola Admin',      'email' => 'admin@uvita.uz',     'password' => $password, 'role' => 'admin',       'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Abdulaziz Manager', 'email' => 'manager@uvita.uz',   'password' => $password, 'role' => 'manager',     'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Bobur Courier',     'email' => 'courier@uvita.uz',   'password' => $password, 'role' => 'courier',     'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Jasur Courier',     'email' => 'courier2@uvita.uz',  'password' => $password, 'role' => 'courier',     'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
