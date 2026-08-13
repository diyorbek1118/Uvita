<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | SMS driver
    |--------------------------------------------------------------------------
    |
    | log   - local development only; no real SMS is sent.
    | eskiz - Eskiz.uz HTTP API.
    | http  - generic JSON HTTP gateway (for an Android/personal-phone gateway
    |         or another provider with a compatible endpoint).
    |
    */
    'driver' => env('SMS_DRIVER', 'log'),
    'from' => env('SMS_FROM', '4546'),

    'eskiz' => [
        'base_url' => rtrim(env('ESKIZ_BASE_URL', 'https://notify.eskiz.uz/api'), '/'),
        'email' => env('ESKIZ_EMAIL', ''),
        'password' => env('ESKIZ_PASSWORD', ''),
        'callback_url' => env('ESKIZ_CALLBACK_URL'),
    ],

    'http' => [
        'url' => env('SMS_HTTP_URL', ''),
        'token' => env('SMS_HTTP_TOKEN', ''),
        'token_header' => env('SMS_HTTP_TOKEN_HEADER', 'Authorization'),
        'token_prefix' => env('SMS_HTTP_TOKEN_PREFIX', 'Bearer'),
        'phone_field' => env('SMS_HTTP_PHONE_FIELD', 'phone'),
        'message_field' => env('SMS_HTTP_MESSAGE_FIELD', 'message'),
        'from_field' => env('SMS_HTTP_FROM_FIELD', 'from'),
    ],
];
