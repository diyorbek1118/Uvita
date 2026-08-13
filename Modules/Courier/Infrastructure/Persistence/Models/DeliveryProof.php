<?php

declare(strict_types=1);

namespace Modules\Courier\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

final class DeliveryProof extends Model
{
    protected $fillable = [
        'order_id', 'pin_hash', 'pin_encrypted', 'pin_attempts', 'pin_locked_until',
        'method', 'recipient_name', 'photo', 'latitude', 'longitude',
        'verified_by_courier_id', 'verified_at',
    ];

    protected $hidden = ['pin_hash', 'pin_encrypted'];

    protected $casts = [
        'pin_locked_until' => 'datetime',
        'verified_at' => 'datetime',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];
}
