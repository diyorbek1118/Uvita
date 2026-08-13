<?php

declare(strict_types=1);

namespace Modules\Seller\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

final class SellerProfileModel extends Model
{
    protected $table = 'seller_profiles';

    protected $fillable = [
        'seller_id', 'business_name', 'legal_type', 'tin', 'phone', 'region', 'district',
        'address', 'bank_account', 'bank_mfo', 'terms_accepted', 'is_verified', 'verified_at', 'verified_by_id',
    ];

    protected function casts(): array
    {
        return [
            'terms_accepted' => 'boolean',
            'is_verified' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }
}
