<?php

return [
    // Production must provide a private, independently durable directory before deletion is enabled.
    'restoration_directory' => env('CHATBOT_ADMIN_RESTORATION_DIRECTORY', env('APP_ENV') === 'production' ? null : storage_path('app/private/chatbot-deletions')),
];
