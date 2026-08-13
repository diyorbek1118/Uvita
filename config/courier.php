<?php

declare(strict_types=1);

return [
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
