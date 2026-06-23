<?php

declare(strict_types=1);

return [

    'defaults' => [
        'guard'     => 'api',
        'passwords' => 'users',
    ],

    'guards' => [
        'web' => [
            'driver'   => 'session',
            'provider' => 'users',
        ],

        'api' => [
            'driver'   => 'sanctum',
            'provider' => 'users',
        ],

        'sanctum' => [
            'driver'   => 'sanctum',
            'provider' => 'staff',
        ],

        'manager' => [
            'driver'   => 'sanctum',
            'provider' => 'staff',
        ],

        'courier' => [
            'driver'   => 'sanctum',
            'provider' => 'staff',
        ],

        'admin' => [
            'driver'   => 'sanctum',
            'provider' => 'staff',
        ],

        'super_admin' => [
            'driver'   => 'sanctum',
            'provider' => 'staff',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model'  => Modules\User\Infrastructure\Persistence\Models\User::class,
        ],

        'staff' => [
            'driver' => 'eloquent',
            'model'  => Modules\Admin\Infrastructure\Persistence\Models\Staff::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table'    => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire'   => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

    // OTP muddati (soniyalarda). Production: 120. Local test uchun .env da oshirish mumkin.
    'otp_ttl_seconds' => (int) env('OTP_TTL_SECONDS', 120),

];
