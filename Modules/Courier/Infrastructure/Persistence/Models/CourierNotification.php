<?php

declare(strict_types=1);

namespace Modules\Courier\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

final class CourierNotification extends Model
{
    protected $fillable = ['courier_id', 'type', 'title', 'body', 'data', 'read_at'];

    protected $casts = ['data' => 'array', 'read_at' => 'datetime'];
}
