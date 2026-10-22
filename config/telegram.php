<?php

return [
    'bot_token' => env('TELEGRAM_BOT_TOKEN', ''),
    'api_base_url' => env('TELEGRAM_API_BASE_URL', 'https://api.telegram.org'),
    'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET', ''),
    'timeout_seconds' => (int) env('TELEGRAM_HTTP_TIMEOUT', 10),
    'results_per_message' => (int) env('TELEGRAM_RESULTS_PER_MESSAGE', 5),
    'location_search_radius' => (int) env('TELEGRAM_LOCATION_SEARCH_RADIUS', 1500),
    'allowed_updates' => ['message', 'callback_query'],
];
