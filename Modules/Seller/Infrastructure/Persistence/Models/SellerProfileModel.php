<?php

declare(strict_types=1);

namespace Modules\Seller\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Modules\Product\Infrastructure\Persistence\Models\Product;

final class SellerProfileModel extends Model
{
    protected $table = 'seller_profiles';

    protected $fillable = [
        'seller_id', 'business_name', 'legal_type', 'tin', 'phone', 'region', 'district',
        'address', 'pickup_latitude', 'pickup_longitude', 'bank_account', 'bank_mfo', 'terms_accepted', 'is_active', 'is_verified', 'verified_at', 'verified_by_id',
    ];

    protected function casts(): array
    {
        return [
            'terms_accepted' => 'boolean',
            'is_active' => 'boolean',
            'is_verified' => 'boolean',
            'verified_at' => 'datetime',
            'pickup_latitude' => 'decimal:7',
            'pickup_longitude' => 'decimal:7',
        ];
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'seller_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'seller_profile_id');
    }
}
