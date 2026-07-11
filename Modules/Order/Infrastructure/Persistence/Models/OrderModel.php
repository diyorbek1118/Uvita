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
        'total_price',
        'service_fee',
        'courier_fee',
        'grand_total',
        'not_found_count',
        'paid_at',
        'confirmed_at',
        'ready_at',
        'delivering_at',
        'delivered_at',
        'delivery_issue_at',
        'cancelled_at',
    ];

    protected $casts = [
        'status'            => OrderStatus::class,
        'address'           => 'array',
        'paid_at'           => 'datetime',
        'confirmed_at'      => 'datetime',
        'ready_at'          => 'datetime',
        'delivering_at'     => 'datetime',
        'delivered_at'      => 'datetime',
        'delivery_issue_at' => 'datetime',
        'cancelled_at'      => 'datetime',
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
