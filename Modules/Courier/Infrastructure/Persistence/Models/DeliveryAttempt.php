<?php

declare(strict_types=1);

namespace Modules\Courier\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Courier\Domain\Enums\DeliveryAttemptReason;

final class DeliveryAttempt extends Model
{
    protected $fillable = [
        'order_id', 'courier_id', 'attempt_number', 'reason_code', 'reason_note',
        'latitude', 'longitude', 'attempted_at',
    ];

    protected $casts = [
        'reason_code' => DeliveryAttemptReason::class,
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'attempted_at' => 'datetime',
    ];
}
