<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class OtpAttempt extends Model
{
    use HasFactory;

    protected $table = 'otp_attempts';

    protected $fillable = [
        'phone',
        'code',
        'type',
        'attempts_count',
        'blocked_until',
        'expires_at',
        'is_verified',
    ];

    protected function casts(): array
    {
        return [
            'blocked_until'  => 'datetime',
            'expires_at'     => 'datetime',
            'is_verified'    => 'boolean',
            'attempts_count' => 'integer',
        ];
    }
}
