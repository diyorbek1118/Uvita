<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Modules\Seller\Infrastructure\Persistence\Models\SellerProfileModel;

final class SellerProfileSeeder extends Seeder
{
    public function run(): void
    {
        $seller = Staff::query()->where('email', 'seller@uvita.uz')->firstOrFail();

        SellerProfileModel::create([
            'seller_id' => $seller->id,
            'business_name' => 'Baraka Agro Market',
            'legal_type' => 'farmer_farm',
            'tin' => '309123456',
            'phone' => '+998901234567',
            'region' => 'Toshkent viloyati',
            'district' => 'Parkent',
            'address' => 'Parkent tumani, Zarkent qishlog‘i, 12-uy',
            'bank_account' => '20208000900000000001',
            'bank_mfo' => '00001',
            'terms_accepted' => true,
            'is_verified' => true,
            'verified_at' => now(),
        ]);
    }
}
