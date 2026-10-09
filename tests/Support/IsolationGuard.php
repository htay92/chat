<?php

namespace Tests\Support;

use RuntimeException;

final class IsolationGuard
{
    public static function verify(array $config): void
    {
        $database = $config['database'] ?? '';
        if (($config['environment'] ?? null) !== 'testing'
            || ($config['db_host'] ?? null) !== 'chatbot-test-mysql'
            || ($config['redis_host'] ?? null) !== 'chatbot-test-redis'
            || ! preg_match('/\Achatbot_test_[a-f0-9]{12}\z/', $database)
            || ($config['username'] ?? null) !== 'chatbot_test'
            || ($config['password'] ?? null) !== 'isolated-test-only'
            || ($config['prefix'] ?? null) !== $database.':'
            || ! empty($config['db_url']) || ! empty($config['redis_url'])
            || ($config['cached'] ?? true) !== false
            || ($config['driver'] ?? null) !== 'mysql'
            || ($config['socket'] ?? null) !== ''
            || (int) ($config['db_port'] ?? 0) !== 3306
            || (int) ($config['redis_port'] ?? 0) !== 6379
            || ($config['redis_password'] ?? null) !== 'isolated-test-only'
            || ($config['overrides'] ?? true) !== false) {
            throw new RuntimeException('Unsafe chatbot test configuration; no connection permitted.');
        }
    }
}
