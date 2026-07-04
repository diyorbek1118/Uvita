<?php

declare(strict_types=1);

namespace Modules\Order\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Order\Domain\Enums\OrderStatus;
use Modules\User\Infrastructure\Persistence\Models\User;

class OrderModel extends Model
{
    use HasFactory;

    protected $table = 'orders';

    protected $fillable = [
        'user_id',
        'courier_id',
        'status',
        'address',
        'phone',
        'phone_secondary',
        'delivery_time',
        'courier_note',
        'delivery_price',
        'total_price',
        'grand_total',
        'not_found_count',
    ];

    protected $casts = [
        'status'  => OrderStatus::class,
        'address' => 'array',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItemModel::class, 'order_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'courier_id');
    }
}
