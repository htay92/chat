<?php

namespace Tests\Unit\Chatbot;

use PHPUnit\Framework\TestCase;
use Tests\Support\IsolationGuard;

class AdminIsolationGuardTest extends TestCase
{
    public function test_resolved_connection_overrides_fail_before_io(): void
    {
        $schema = 'chatbot_test_123456abcdef';
        $sql = ['driver' => 'mysql', 'host' => 'chatbot-test-mysql', 'port' => 3306, 'database' => $schema, 'username' => 'chatbot_test', 'password' => 'isolated-test-only', 'unix_socket' => '', 'url' => null];
        $redis = ['host' => 'chatbot-test-redis', 'port' => 6379, 'password' => 'isolated-test-only', 'url' => null];
        $safe = ['environment' => 'testing', 'cached' => false, 'session' => ['driver' => 'database', 'connection' => 'mysql'], 'database' => ['default' => 'mysql', 'connections' => ['mysql' => $sql], 'redis' => ['options' => ['prefix' => $schema.':'], 'default' => $redis, 'chatbot' => $redis]]];
        IsolationGuard::verifyResolvedAdmin($safe);
        $this->addToAssertionCount(1);
        foreach (['persistent' => true, 'cluster' => 'predis', 'replication' => 'sentinel'] as $key => $value) {
            $bad = $safe;
            $bad['database']['redis']['options'][$key] = $value;
            $rejected = false;
            try {
                IsolationGuard::verifyResolvedAdmin($bad);
            } catch (\RuntimeException) {
                $rejected = true;
            }
            $this->assertTrue($rejected, 'Unsafe Redis routing option accepted');
        }
        foreach (['host', 'port', 'password', 'url'] as $field) {
            $bad = $safe;
            $bad['database']['redis']['chatbot'][$field] = 'unsafe';
            $rejected = false;
            try {
                IsolationGuard::verifyResolvedAdmin($bad);
            } catch (\RuntimeException) {
                $rejected = true;
            }
            $this->assertTrue($rejected, 'Unsafe Redis override accepted');
        }
        foreach ([['read' => ['host' => 'production']], ['write' => ['host' => 'production']], ['url' => 'mysql://production'], ['database' => 'development']] as $override) {
            $bad = $safe;
            $bad['database']['connections']['mysql'] = array_replace($sql, $override);
            $rejected = false;
            try {
                IsolationGuard::verifyResolvedAdmin($bad);
            } catch (\RuntimeException) {
                $rejected = true;
            }
            $this->assertTrue($rejected, 'Unsafe SQL override accepted');
        }
    }

    public function test_session_and_secondary_connections_fail_closed_without_io(): void
    {
        IsolationGuard::verifyAdmin('array', null, 'mysql', ['mysql'], ['default', 'chatbot']);
        IsolationGuard::verifyAdmin('database', 'mysql', 'mysql', ['mysql'], ['default', 'chatbot']);
        $this->addToAssertionCount(2);
        foreach ([['redis', null, 'mysql', ['mysql'], ['default', 'chatbot']], ['database', 'production', 'mysql', ['mysql'], ['default', 'chatbot']], ['database', null, 'sqlite', ['mysql'], ['default', 'chatbot']], ['array', null, 'mysql', ['mysql', 'production'], ['default', 'chatbot']], ['array', null, 'mysql', ['mysql'], ['default', 'chatbot', 'production']]] as $args) {
            $rejected = false;
            try {
                IsolationGuard::verifyAdmin(...$args);
            } catch (\RuntimeException) {
                $rejected = true;
            }
            $this->assertTrue($rejected, 'Unsafe admin connection accepted');
        }
    }
}
