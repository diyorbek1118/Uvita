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

        'manager' => [
            'driver'   => 'session',
            'provider' => 'managers',
        ],

        'courier' => [
            'driver'   => 'session',
            'provider' => 'couriers',
        ],

        'admin' => [
            'driver'   => 'session',
            'provider' => 'admins',
        ],

        'super_admin' => [
            'driver'   => 'session',
            'provider' => 'super_admins',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model'  => Modules\User\Infrastructure\Persistence\Models\User::class,
        ],

        'managers' => [
            'driver' => 'eloquent',
            'model'  => 'Modules\Manager\Infrastructure\Persistence\Models\Manager',
        ],

        'couriers' => [
            'driver' => 'eloquent',
            'model'  => 'Modules\Courier\Infrastructure\Persistence\Models\Courier',
        ],

        'admins' => [
            'driver' => 'eloquent',
            'model'  => 'Modules\Admin\Infrastructure\Persistence\Models\Admin',
        ],

        'super_admins' => [
            'driver' => 'eloquent',
            'model'  => 'Modules\Admin\Infrastructure\Persistence\Models\SuperAdmin',
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
