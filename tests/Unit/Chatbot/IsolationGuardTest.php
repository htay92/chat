<?php

namespace Tests\Unit\Chatbot;

use PHPUnit\Framework\TestCase;
use Tests\Support\IsolationGuard;

class IsolationGuardTest extends TestCase
{
    public function test_accepts_only_explicit_isolated_configuration(): void
    {
        IsolationGuard::verify($this->safe());
        $this->addToAssertionCount(1);
    }

    public function test_rejects_unsafe_configuration_without_connecting(): void
    {
        foreach (['environment' => 'local', 'db_host' => 'mysql', 'redis_host' => 'redis', 'database' => 'testing', 'username' => 'root', 'password' => '', 'prefix' => '', 'db_url' => 'mysql://elsewhere', 'redis_url' => 'redis://elsewhere', 'cached' => true, 'driver' => 'sqlite', 'socket' => '/development/mysql.sock', 'db_port' => 3307, 'redis_port' => 6380, 'redis_password' => '', 'overrides' => true] as $key => $value) {
            try {
                IsolationGuard::verify(array_replace($this->safe(), [$key => $value]));
                $this->fail("Unsafe $key accepted");
            } catch (\RuntimeException $e) {
                $this->assertSame('Unsafe chatbot test configuration; no connection permitted.', $e->getMessage());
            }
        }
    }

    private function safe(): array
    {
        return ['environment' => 'testing', 'db_host' => 'chatbot-test-mysql', 'redis_host' => 'chatbot-test-redis', 'database' => 'chatbot_test_abcdef123456', 'username' => 'chatbot_test', 'password' => 'isolated-test-only', 'prefix' => 'chatbot_test_abcdef123456:', 'db_url' => '', 'redis_url' => '', 'cached' => false, 'driver' => 'mysql', 'socket' => '', 'db_port' => 3306, 'redis_port' => 6379, 'redis_password' => 'isolated-test-only', 'overrides' => false];
    }
}
