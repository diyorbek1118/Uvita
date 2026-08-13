<?php

declare(strict_types=1);

namespace Modules\Admin\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Admin\Domain\Enums\StaffRole;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Courier\Infrastructure\Persistence\Models\CourierProfile;

class Staff extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $table = 'staff';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'role'      => StaffRole::class,
        'is_active' => 'boolean',
        'password'  => 'hashed',
    ];

    public function courierProfile(): HasOne
    {
        return $this->hasOne(CourierProfile::class, 'courier_id');
    }
}
