<?php

declare(strict_types=1);

namespace Modules\Courier\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

final class CourierLocation extends Model
{
    protected $fillable = ['courier_id', 'order_id', 'latitude', 'longitude', 'accuracy', 'recorded_at'];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'recorded_at' => 'datetime',
    ];
}
