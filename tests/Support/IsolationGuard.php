<?php

namespace Tests\Support;

use RuntimeException;

final class IsolationGuard
{
    public static function verifyResolvedAdmin(array $config): void
    {
        $database = $config['database'];
        $session = $config['session'];
        $options = $database['redis']['options'];
        if (! empty($options['persistent']) || ! empty($options['replication'])
            || (isset($options['cluster']) && $options['cluster'] !== 'redis')
            || isset($database['redis']['clusters'])) {
            throw new RuntimeException('Unsafe Redis routing options; no connection permitted.');
        }
        self::verifyAdmin($session['driver'], $session['connection'] ?? null, $database['default'], ['mysql'], ['default', 'chatbot']);
        $sql = $database['connections']['mysql'];
        $redis = $database['redis']['default'];
        self::verify([
            'environment' => $config['environment'], 'cached' => $config['cached'],
            'db_host' => $sql['host'], 'database' => $sql['database'], 'username' => $sql['username'], 'password' => $sql['password'],
            'driver' => $sql['driver'], 'socket' => $sql['unix_socket'], 'db_port' => $sql['port'], 'db_url' => $sql['url'] ?? null,
            'overrides' => isset($sql['read']) || isset($sql['write']), 'prefix' => $database['redis']['options']['prefix'],
            'redis_host' => $redis['host'], 'redis_port' => $redis['port'], 'redis_password' => $redis['password'], 'redis_url' => $redis['url'] ?? null,
        ]);
        foreach (['default', 'chatbot'] as $name) {
            $connection = $database['redis'][$name];
            if ($connection['host'] !== 'chatbot-test-redis' || (int) $connection['port'] !== 6379
                || $connection['password'] !== 'isolated-test-only' || ! empty($connection['url'])
                || ! empty($connection['persistent']) || ! empty($connection['options']['prefix'])) {
                throw new RuntimeException('Unsafe resolved Redis configuration; no connection permitted.');
            }
        }
    }

    public static function verifyAdmin(string $sessionDriver, ?string $sessionConnection, string $defaultConnection, array $sqlConnections, array $redisConnections): void
    {
        if (! in_array($sessionDriver, ['array', 'database'], true)
            || ($sessionConnection !== null && $sessionConnection !== 'mysql')
            || $defaultConnection !== 'mysql'
            || $sqlConnections !== ['mysql']
            || $redisConnections !== ['default', 'chatbot']) {
            throw new RuntimeException('Unsafe admin test connection; no connection permitted.');
        }
    }

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
