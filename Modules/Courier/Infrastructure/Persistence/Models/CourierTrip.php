<?php

declare(strict_types=1);

namespace Modules\Courier\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Modules\Courier\Domain\Enums\CourierTripStatus;

final class CourierTrip extends Model
{
    protected $fillable = [
        'courier_id', 'origin_region', 'destination_region', 'status', 'capacity_kg',
        'total_weight_kg', 'orders_count', 'cargo_value', 'total_courier_fee',
        'cash_collected', 'accepted_at', 'pickups_completed_at', 'completed_at',
        'cancelled_at', 'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => CourierTripStatus::class,
            'capacity_kg' => 'decimal:3',
            'total_weight_kg' => 'decimal:3',
            'orders_count' => 'integer',
            'cargo_value' => 'integer',
            'total_courier_fee' => 'integer',
            'cash_collected' => 'integer',
            'accepted_at' => 'datetime',
            'pickups_completed_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'courier_id');
    }

    public function tripOrders(): HasMany
    {
        return $this->hasMany(CourierTripOrder::class, 'trip_id');
    }
}
