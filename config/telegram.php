<?php

declare(strict_types=1);

return [
    'bot_token'       => env('TELEGRAM_BOT_TOKEN', ''),
    'manager_chat_id' => env('TELEGRAM_ADMIN_CHAT_ID', ''),
    'api_url'         => 'https://api.telegram.org/bot',
];
