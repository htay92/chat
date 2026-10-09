<?php

return [
    'origin' => env('CHATBOT_ORIGIN', ''),
    'hmac_key' => env('CHATBOT_HMAC_KEY', ''),
    'fallback' => 'Thanks for your question. We don’t have a prepared answer for that yet.',
    'retention_days' => 90,
    'trusted_proxies' => array_values(array_filter(explode(',', env('CHATBOT_TRUSTED_PROXIES', '')))),
    'operator_users' => array_values(array_filter(explode(',', env('CHATBOT_OPERATOR_USERS', in_array(env('APP_ENV'), ['local', 'testing'], true) ? 'sail' : '')))),
];
