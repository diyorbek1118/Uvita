<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Modules\Category\Infrastructure\Persistence\Models\Category as CategoryModel;
use Modules\Product\Domain\Enums\ProductStatusEnum;
use Modules\Seller\Infrastructure\Persistence\Models\SellerProfileModel;

final class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'products';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'stock',
        'reserved_stock',
        'rating',
        'reviews_count',
        'status',
        'images',
        'category_id',
        'manager_id',
        'seller_id',
        'seller_profile_id',
        'approved_version',
        'fee_snapshot',
        'video_url',
        'primary_image_index',
        'origin_region',
        'farmer_name',
        'unit',
        'minimum_order_quantity',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'stock' => 'integer',
            'reserved_stock' => 'integer',
            'rating' => 'float',
            'reviews_count' => 'integer',
            'images' => 'array',
            'status' => ProductStatusEnum::class,
            'manager_id' => 'integer',
            'seller_id' => 'integer',
            'seller_profile_id' => 'integer',
            'approved_version' => 'integer',
            'fee_snapshot' => 'array',
            'primary_image_index' => 'integer',
            'minimum_order_quantity' => 'integer',
        ];
    }

    public function getAvailableStockAttribute(): int
    {
        return max(0, (int) $this->stock - (int) $this->reserved_stock);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CategoryModel::class, 'category_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'manager_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'seller_id');
    }

    public function sellerProfile(): BelongsTo
    {
        return $this->belongsTo(SellerProfileModel::class, 'seller_profile_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(ProductRevision::class);
    }
}
