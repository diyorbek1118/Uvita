<?php

declare(strict_types=1);

namespace Modules\Courier\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;

final class CourierProfile extends Model
{
    protected $fillable = [
        'courier_id', 'phone', 'vehicle_type', 'vehicle_number', 'photo',
        'vehicle_capacity_kg', 'max_orders_per_trip',
        'is_online', 'shift_started_at', 'shift_ended_at', 'last_seen_at',
    ];

    protected $casts = [
        'is_online' => 'boolean',
        'vehicle_capacity_kg' => 'decimal:3',
        'max_orders_per_trip' => 'integer',
        'shift_started_at' => 'datetime',
        'shift_ended_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'courier_id');
    }
}
