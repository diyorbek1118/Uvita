<?php

declare(strict_types=1);
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Modules\User\Infrastructure\Persistence\Models\User;

return [

    'defaults' => [
        'guard' => 'api',
        'passwords' => 'users',
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        'api' => [
            'driver' => 'sanctum',
            'provider' => 'users',
        ],

        'sanctum' => [
            'driver' => 'sanctum',
            'provider' => 'staff',
        ],

        'manager' => [
            'driver' => 'sanctum',
            'provider' => 'staff',
        ],

        'courier' => [
            'driver' => 'sanctum',
            'provider' => 'staff',
        ],

        'admin' => [
            'driver' => 'sanctum',
            'provider' => 'staff',
        ],

        'super_admin' => [
            'driver' => 'sanctum',
            'provider' => 'staff',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => User::class,
        ],

        'staff' => [
            'driver' => 'eloquent',
            'model' => Staff::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

    // OTP muddati (soniyalarda). Production: 120. Local test uchun .env da oshirish mumkin.
    'otp_ttl_seconds' => (int) env('OTP_TTL_SECONDS', 120),

    'customer_token_expiration_days' => (int) env('CUSTOMER_TOKEN_EXPIRATION_DAYS', 30),
    'staff_token_expiration_hours' => (int) env('STAFF_TOKEN_EXPIRATION_HOURS', 12),

];
