<?php

declare(strict_types=1);

return [
    'bot_token' => env('TELEGRAM_BOT_TOKEN'),

    'chat_ids' => [
        'manager' => array_filter(
            explode(',', env('TELEGRAM_MANAGER_CHAT_IDS', ''))
        ),
        'admin' => array_filter(
            explode(',', env('TELEGRAM_ADMIN_CHAT_IDS', ''))
        ),
        'courier' => array_filter(
            explode(',', env('TELEGRAM_COURIER_CHAT_IDS', ''))
        ),
    ],
];
