<?php

declare(strict_types=1);

namespace Modules\Courier\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

final class CourierDevice extends Model
{
    protected $fillable = ['courier_id', 'token', 'platform', 'device_name', 'is_active', 'last_used_at'];

    protected $casts = ['is_active' => 'boolean', 'last_used_at' => 'datetime'];
}
