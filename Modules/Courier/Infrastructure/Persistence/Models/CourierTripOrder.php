<?php

declare(strict_types=1);

namespace Modules\Courier\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Order\Infrastructure\Persistence\Models\OrderModel;

final class CourierTripOrder extends Model
{
    protected $fillable = [
        'trip_id', 'order_id', 'pickup_key', 'pickup_sequence', 'delivery_sequence',
        'weight_kg', 'picked_up_at', 'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'pickup_sequence' => 'integer',
            'delivery_sequence' => 'integer',
            'weight_kg' => 'decimal:3',
            'picked_up_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(CourierTrip::class, 'trip_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(OrderModel::class, 'order_id');
    }
}
