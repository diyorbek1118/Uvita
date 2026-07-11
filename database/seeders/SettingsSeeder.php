<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Admin\Infrastructure\Persistence\Models\SettingModel;

/**
 * Tizim sozlamalari.
 * Yetkazish mijozdan OLINMAYDI: mijoz mahsulot + 15% xizmat haqi to'laydi;
 * kuryer haqi platformadan (kodda, OrderFeeCalculator).
 */
class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        SettingModel::insert([
            ['key' => 'delivery_city',              'value' => 'Toshkent', 'description' => 'Yetkazish shahri'],
            ['key' => 'min_order_amount',           'value' => '50000',    'description' => "Minimal buyurtma summasi (so'm)"],
            ['key' => 'otp_expiry_seconds',         'value' => '120',      'description' => 'OTP amal qilish vaqti (soniya)'],
            ['key' => 'otp_max_attempts',           'value' => '5',        'description' => 'OTP maksimal urinishlar'],
            ['key' => 'otp_block_minutes',          'value' => '10',       'description' => 'OTP blok vaqti (daqiqa)'],
            ['key' => 'max_not_found_attempts',     'value' => '3',        'description' => 'Kuryer "topilmadi" maksimal urinish'],
            ['key' => 'review_request_delay_hours', 'value' => '24',       'description' => "Yetkazilgach review so'rash (soat)"],
        ]);
    }
}
