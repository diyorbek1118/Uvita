<?php

declare(strict_types=1);

namespace Modules\Admin\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

final class StaffDeviceToken extends Model
{
    protected $table = 'staff_device_tokens';

    protected $fillable = [
        'staff_id',
        'token',
        'platform',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
