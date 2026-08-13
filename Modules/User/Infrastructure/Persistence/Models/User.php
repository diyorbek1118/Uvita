<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Deal\Infrastructure\Persistence\Models\DealModel;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;
use Modules\Rating\Infrastructure\Persistence\Models\RatingModel;

final class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'surname',
        'phone',
        'region',
        'district',
        'address',
        'lat',
        'lng',
        'avatar',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(OrderModel::class, 'user_id');
    }

    /** Foydalanuvchiga yozilgan baholashlar (rated_id) */
    public function ratingsReceived(): HasMany
    {
        return $this->hasMany(RatingModel::class, 'rated_id');
    }

    /** Foydalanuvchi yozgan baholashlar (rater_id) */
    public function ratingsGiven(): HasMany
    {
        return $this->hasMany(RatingModel::class, 'rater_id');
    }

    /** Sotuvchi sifatidagi bitimlar (deals.seller_id) */
    public function sales(): HasMany
    {
        return $this->hasMany(DealModel::class, 'seller_id');
    }

    /** Xaridor sifatidagi bitimlar (deals.buyer_id) */
    public function purchases(): HasMany
    {
        return $this->hasMany(DealModel::class, 'buyer_id');
    }
}
