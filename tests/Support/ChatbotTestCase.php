<?php

namespace Tests\Support;

use Illuminate\Support\Facades\DB;
use PDO;
use Tests\TestCase;

abstract class ChatbotTestCase extends TestCase
{
    protected bool $transactional = true;

    protected function setUp(): void
    {
        if (getenv('CHATBOT_TEST_RUN') !== '1') {
            $this->markTestSkipped('Requires the guarded dedicated Sail test runner.');
        }
        parent::setUp();
        IsolationGuard::verifyResolvedAdmin(['environment' => app()->environment(), 'cached' => app()->configurationIsCached(), 'session' => config('session'), 'database' => config('database')]);
        $db = config('database.connections.mysql');
        IsolationGuard::verify([
            'environment' => app()->environment(), 'db_host' => $db['host'],
            'redis_host' => config('database.redis.default.host'), 'database' => $db['database'],
            'username' => $db['username'], 'password' => $db['password'],
            'prefix' => config('database.redis.options.prefix'), 'db_url' => $db['url'],
            'redis_url' => config('database.redis.default.url'), 'cached' => app()->configurationIsCached(),
            'driver' => config('database.default') === 'mysql' ? $db['driver'] : '',
            'socket' => $db['unix_socket'], 'db_port' => $db['port'],
            'redis_port' => config('database.redis.default.port'),
            'redis_password' => config('database.redis.default.password'),
            'overrides' => isset($db['read']) || isset($db['write']),
        ]);
        if (config('database.redis.chatbot.host') !== 'chatbot-test-redis' || config('database.redis.chatbot.password') !== 'isolated-test-only' || (int) config('database.redis.chatbot.port') !== 6379 || config('database.redis.chatbot.url')) {
            throw new \RuntimeException('Unsafe chatbot Redis test configuration; no connection permitted.');
        }
        config(['database.redis.chatbot.options.prefix' => $db['database'].':'.bin2hex(random_bytes(6)).':']);
        // Dedicated isolated host; this user has no DROP/GRANT or development credentials.
        $pdo = new PDO('mysql:host='.$db['host'].';port=3306', $db['username'], $db['password']);
        $pdo->exec('CREATE DATABASE IF NOT EXISTS `'.$db['database'].'` CHARACTER SET utf8mb4 COLLATE utf8mb4_bin');
        foreach (glob(database_path('migrations/*chatbot*.php')) as $file) {
            if (str_contains($file, 'chatbot_admin')) {
                continue;
            }
            $migration = require $file;
            $migration->up();
        }
        if ($this->transactional) {
            DB::beginTransaction();
        }
    }

    protected function tearDown(): void
    {
        if (isset($this->app) && $this->app && $this->transactional && DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        parent::tearDown();
    }
}
