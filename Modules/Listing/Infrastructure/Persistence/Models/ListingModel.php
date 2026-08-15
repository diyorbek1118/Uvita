<?php

declare(strict_types=1);

namespace Modules\Listing\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Category\Infrastructure\Persistence\Models\Category;
use Modules\Listing\Domain\Enums\ListingStatus;
use Modules\Rating\Infrastructure\Persistence\Models\RatingModel;
use Modules\User\Infrastructure\Persistence\Models\User as UserModel;

class ListingModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'listings';

    protected $fillable = [
        'seller_id',
        'category_id',
        'title',
        'slug',
        'description',
        'price',
        'quantity',
        'unit',
        'images',
        'video',
        'region',
        'district',
        'address',
        'lat',
        'lng',
        'details',
        'contacts',
        'expires_at',
        'status',
        'rejection_reason',
        'views',
    ];

    protected $casts = [
        'status' => ListingStatus::class,
        'images' => 'array',
        'details' => 'array',
        'contacts' => 'array',
        'price' => 'integer',
        'quantity' => 'float',
        'lat' => 'float',
        'lng' => 'float',
        'views' => 'integer',
        'expires_at' => 'datetime',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'seller_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /** Sotuvchiga yozilgan baholashlar (rating statistika uchun) */
    public function sellerRatings(): HasMany
    {
        return $this->hasMany(RatingModel::class, 'rated_id', 'seller_id');
    }

    /** Shu mahsulotga (e'longa) yozilgan sharhlar */
    public function listingRatings(): HasMany
    {
        return $this->hasMany(RatingModel::class, 'listing_id');
    }
}
