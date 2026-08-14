<?php

declare(strict_types=1);

namespace Modules\Seller\Application\Handlers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Admin\Domain\Enums\StaffRole;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Modules\Seller\Infrastructure\Persistence\Models\SellerProfileModel;

final class CreateSellerHandler
{
    public function handle(array $data): Staff
    {
        return DB::transaction(function () use ($data): Staff {
            $seller = Staff::create([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => 'seller.'.preg_replace('/\D/', '', $data['phone']).'@uvita.local',
                'password' => $data['password'],
                'role' => StaffRole::SELLER,
                'is_active' => true,
            ]);

            $this->createShop($seller->id, $data);

            return $seller->load('sellerProfiles');
        });
    }

    public function createShop(int $sellerId, array $data): SellerProfileModel
    {
        return SellerProfileModel::create([
            'seller_id' => $sellerId,
            'business_name' => $data['business_name'],
            'legal_type' => 'individual',
            'tin' => 'P'.Str::upper(Str::random(13)),
            'phone' => Staff::query()->findOrFail($sellerId)->phone,
            'region' => $data['region'],
            'district' => $data['district'],
            'address' => $data['address'],
            'bank_account' => '',
            'bank_mfo' => '',
            'terms_accepted' => false,
            'is_active' => true,
            'is_verified' => false,
        ]);
    }
}
