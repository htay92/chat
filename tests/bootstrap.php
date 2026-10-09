<?php

use Tests\Support\IsolationGuard;

require __DIR__.'/../vendor/autoload.php';

// No connection occurs here. Real-service tests are opt-in through the isolated runner.
if (getenv('CHATBOT_TEST_RUN') === '1') {
    $run = getenv('CHATBOT_TEST_SCHEMA') ?: 'chatbot_test_'.bin2hex(random_bytes(6));
    IsolationGuard::verify([
        'environment' => getenv('APP_ENV'), 'db_host' => getenv('DB_HOST'),
        'redis_host' => getenv('REDIS_HOST'), 'database' => $run,
        'username' => getenv('DB_USERNAME'), 'password' => getenv('DB_PASSWORD'),
        'prefix' => $run.':', 'db_url' => getenv('DB_URL'),
        'redis_url' => getenv('REDIS_URL'), 'cached' => file_exists(__DIR__.'/../bootstrap/cache/config.php'),
        'driver' => 'mysql', 'socket' => getenv('DB_SOCKET') ?: '',
        'db_port' => getenv('DB_PORT') ?: 3306, 'redis_port' => getenv('REDIS_PORT') ?: 6379,
        'redis_password' => getenv('REDIS_PASSWORD'), 'overrides' => false,
    ]);
    foreach (['DB_CONNECTION' => 'mysql', 'DB_DATABASE' => $run, 'CHATBOT_TEST_SCHEMA' => $run, 'REDIS_PREFIX' => $run.':', 'CHATBOT_REDIS_HOST' => 'chatbot-test-redis', 'CHATBOT_REDIS_PASSWORD' => 'isolated-test-only', 'CHATBOT_HMAC_KEY' => 'test-only-key-at-least-thirty-two-bytes', 'CHATBOT_ORIGIN' => 'http://localhost:9000', 'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array'] as $key => $value) {
        putenv($key.'='.$value);
        $_ENV[$key] = $_SERVER[$key] = $value;
    }
}
