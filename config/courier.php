<?php

declare(strict_types=1);

return [
    'trip' => [
        'max_cargo_value' => (int) env('COURIER_TRIP_MAX_CARGO_VALUE', 50000000),
        'default_capacity_kg' => (float) env('COURIER_DEFAULT_CAPACITY_KG', 1000),
        'default_max_orders' => (int) env('COURIER_DEFAULT_MAX_ORDERS', 10),
    ],
    'delivery_pin' => [
        'max_attempts' => (int) env('COURIER_PIN_MAX_ATTEMPTS', 5),
        'block_minutes' => (int) env('COURIER_PIN_BLOCK_MINUTES', 10),
    ],
    'fcm' => [
        'project_id' => env('FCM_PROJECT_ID'),
        'client_email' => env('FCM_CLIENT_EMAIL'),
        'private_key' => env('FCM_PRIVATE_KEY'),
        'token_uri' => env('FCM_TOKEN_URI', 'https://oauth2.googleapis.com/token'),
    ],
];
