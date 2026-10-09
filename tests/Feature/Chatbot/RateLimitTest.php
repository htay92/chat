<?php

namespace Tests\Feature\Chatbot;

use App\Chatbot\AtomicQuestionLimiter;
use App\Chatbot\SessionIdentity;
use App\Models\ChatbotQuestion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tests\Support\ChatbotTestCase;

class RateLimitTest extends ChatbotTestCase
{
    public function test_real_redis_threshold_and_non_sliding_expiry(): void
    {
        $limiter = app(AtomicQuestionLimiter::class);
        for ($i = 0; $i < 10; $i++) {
            $this->assertSame(0, $limiter->acquire('session:test', 10));
        }
        $this->assertGreaterThan(0, $limiter->acquire('session:test', 10));
        $ttl = Redis::connection('chatbot')->pttl('session:test');
        $this->assertGreaterThan(58000, $ttl);
        $this->assertLessThanOrEqual(60000, $ttl);
        usleep(20000);
        $limiter->acquire('session:test', 10);
        $this->assertLessThan($ttl, Redis::connection('chatbot')->pttl('session:test'));
    }

    public function test_parallel_acquisition_admits_no_more_than_quota(): void
    {
        DB::rollBack();
        DB::disconnect();
        $files = [];
        $pids = [];
        for ($i = 0; $i < 16; $i++) {
            $file = tempnam(sys_get_temp_dir(), 'chatbot-quota-');
            $files[] = $file;
            $pid = pcntl_fork();
            if ($pid === 0) {
                Redis::purge('chatbot');
                try {
                    file_put_contents($file, (string) app(AtomicQuestionLimiter::class)->acquire('parallel:session', 10));
                    exit(0);
                } catch (\Throwable) {
                    exit(1);
                }
            }
            $pids[] = $pid;
        }
        foreach ($pids as $pid) {
            pcntl_waitpid($pid, $status);
            $this->assertSame(0, pcntl_wexitstatus($status));
        }
        $values = array_map(fn ($f) => (int) file_get_contents($f), $files);
        foreach ($files as $file) {
            unlink($file);
        }
        $this->assertSame(10, count(array_filter($values, fn ($n) => $n === 0)));
        DB::beginTransaction();
    }

    public function test_real_sixty_second_window_resets_without_sliding(): void
    {
        $l = app(AtomicQuestionLimiter::class);
        $l->acquire('window:session', 1);
        $this->assertGreaterThan(0, $l->acquire('window:session', 1));
        $deadline = microtime(true) + 60.2;
        while (microtime(true) < $deadline) {
            usleep(500000);
        }
        $this->assertSame(0, $l->acquire('window:session', 1));
    }

    public function test_invalid_attempts_and_session_denials_consume_ip_budget(): void
    {
        $payload = ['question' => 'Hi', 'session_id' => '00000000-0000-4000-8000-000000000001', 'request_id' => '00000000-0000-4000-8000-000000000002'];
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/chatbot/questions', $payload, ['Origin' => 'http://localhost:9000'])->assertStatus($i ? 200 : 201);
        }
        $this->postJson('/api/chatbot/questions', $payload, ['Origin' => 'http://localhost:9000'])->assertStatus(429)->assertHeader('Retry-After');
        for ($i = 11; $i < 30; $i++) {
            $this->postJson('/api/chatbot/questions', ['session_id' => 'bad'], ['Origin' => 'http://localhost:9000'])->assertStatus(422);
        }
        $this->postJson('/api/chatbot/questions', ['session_id' => 'bad'], ['Origin' => 'http://localhost:9000'])->assertStatus(429);
        $this->assertSame(1, ChatbotQuestion::count());
    }

    public function test_unavailable_redis_fails_closed_without_insert(): void
    {
        config(['database.redis.chatbot.port' => 6399]);
        Redis::purge('chatbot');
        $this->postJson('/api/chatbot/questions', ['question' => 'Hi', 'session_id' => '00000000-0000-4000-8000-000000000001', 'request_id' => '00000000-0000-4000-8000-000000000002'], ['Origin' => 'http://localhost:9000'])->assertStatus(503);
        $this->assertSame(0, ChatbotQuestion::count());
    }

    public function test_explicit_proxy_trust_ignores_spoofed_headers_and_provider_host(): void
    {
        $payload = ['question' => 'Hi', 'session_id' => '00000000-0000-4000-8000-000000000001', 'request_id' => '00000000-0000-4000-8000-000000000002'];
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.10'])->postJson('/api/chatbot/questions', $payload, ['Origin' => 'http://localhost:9000', 'Host' => 'evil.on-forge.com', 'X-Forwarded-For' => '198.51.100.1'])->assertCreated();
        $key = 'chatbot:ip:'.app(SessionIdentity::class)->hash('192.0.2.10', 'ip');
        $this->assertSame('1', (string) Redis::connection('chatbot')->get($key));
        config(['chatbot.trusted_proxies' => ['192.0.2.10']]);
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.10'])->postJson('/api/chatbot/questions', $payload, ['Origin' => 'http://localhost:9000', 'X-Forwarded-For' => '198.51.100.1', 'X-Forwarded-Proto' => 'https', 'X-Forwarded-Port' => '443'])->assertOk();
        $key = 'chatbot:ip:'.app(SessionIdentity::class)->hash('198.51.100.1', 'ip');
        $this->assertSame('1', (string) Redis::connection('chatbot')->get($key));
        config(['chatbot.trusted_proxies' => ['192.0.2.10/999']]);
        $this->postJson('/api/chatbot/questions', $payload, ['Origin' => 'http://localhost:9000'])->assertStatus(503);
        config(['chatbot.trusted_proxies' => ['*']]);
        $this->postJson('/api/chatbot/questions', $payload, ['Origin' => 'http://localhost:9000'])->assertStatus(503);
    }
}
