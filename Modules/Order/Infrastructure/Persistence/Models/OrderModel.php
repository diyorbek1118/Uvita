<?php

declare(strict_types=1);

namespace Modules\Order\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Modules\Order\Domain\Enums\OrderStatus;
use Modules\Payment\Infrastructure\Persistence\Models\PaymentModel;
use Modules\Review\Infrastructure\Persistence\Models\ReviewModel;
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
        'delivery_latitude',
        'delivery_longitude',
        'lat',
        'lng',
        'geo_level',
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
        'status' => OrderStatus::class,
        'address' => 'array',
        'delivery_latitude' => 'decimal:7',
        'delivery_longitude' => 'decimal:7',
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
        'paid_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'ready_at' => 'datetime',
        'delivering_at' => 'datetime',
        'delivered_at' => 'datetime',
        'delivery_issue_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItemModel::class, 'order_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ReviewModel::class, 'order_id');
    }

    public function latestPayment(): HasOne
    {
        return $this->hasOne(PaymentModel::class, 'order_id')->latestOfMany();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'courier_id');
    }

    public function deliveryAssignments(): HasMany
    {
        return $this->hasMany(\Modules\Courier\Infrastructure\Persistence\Models\DeliveryAssignment::class, 'order_id');
    }

    public function deliveryAttempts(): HasMany
    {
        return $this->hasMany(\Modules\Courier\Infrastructure\Persistence\Models\DeliveryAttempt::class, 'order_id');
    }

    public function deliveryProof(): HasOne
    {
        return $this->hasOne(\Modules\Courier\Infrastructure\Persistence\Models\DeliveryProof::class, 'order_id');
    }
}
