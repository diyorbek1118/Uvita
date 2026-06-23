<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Admin\Infrastructure\Persistence\Models\SettingModel;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        SettingModel::insert([
            [
                'key'         => 'delivery_price',
                'value'       => '15000',
                'description' => "Yetkazish narxi (so'mda)",
            ],
            [
                'key'         => 'delivery_city',
                'value'       => 'Toshkent',
                'description' => 'Yetkazish amalga oshiriladigan shahar',
            ],
            [
                'key'         => 'min_order_amount',
                'value'       => '50000',
                'description' => "Minimal buyurtma summasi (so'mda)",
            ],
            [
                'key'         => 'otp_expiry_seconds',
                'value'       => '120',
                'description' => 'OTP amal qilish vaqti (soniyada)',
            ],
            [
                'key'         => 'otp_max_attempts',
                'value'       => '5',
                'description' => 'OTP maksimal urinishlar soni',
            ],
            [
                'key'         => 'otp_block_minutes',
                'value'       => '10',
                'description' => 'OTP blok vaqti (daqiqada)',
            ],
            [
                'key'         => 'max_not_found_attempts',
                'value'       => '3',
                'description' => 'Kuryer topilmadi maksimal urinish',
            ],
            [
                'key'         => 'review_request_delay_hours',
                'value'       => '24',
                'description' => "Yetkazilgandan so'ng review so'rash (soatda)",
            ],
        ]);
    }
}
