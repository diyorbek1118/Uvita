<?php

declare(strict_types=1);

namespace Modules\Courier\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Courier\Domain\Enums\CourierPayoutStatus;

final class CourierPayout extends Model
{
    protected $fillable = [
        'courier_id', 'period_start', 'period_end', 'amount', 'status',
        'note', 'created_by', 'paid_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'status' => CourierPayoutStatus::class,
        'paid_at' => 'datetime',
    ];
}
