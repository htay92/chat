<?php

namespace Tests\Feature\Admin;

use App\Chatbot\Admin\AdminLimiter;
use App\Chatbot\SessionIdentity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Support\AdminTestCase;

class AdminLimiterTest extends AdminTestCase
{
    public function test_atomic_parallel_quotas_and_private_key_domains(): void
    {
        DB::rollBack();
        DB::disconnect();
        foreach (['admin-login-account' => 5, 'admin-login-ip' => 20, 'admin-request-user' => 120] as $domain => $maximum) {
            $files = [];
            $pids = [];
            for ($i = 0; $i < $maximum + 4; $i++) {
                $file = tempnam(sys_get_temp_dir(), 'admin-quota-');
                $files[] = $file;
                $pid = pcntl_fork();
                if ($pid === 0) {
                    Redis::purge('chatbot');
                    try {
                        app(AdminLimiter::class)->acquire($domain, 'synthetic', $maximum);
                        $result = 0;
                    } catch (HttpException $e) {
                        $result = $e->getStatusCode();
                    }
                    file_put_contents($file, (string) $result);
                    exit(0);
                }
                $pids[] = $pid;
            }
            foreach ($pids as $pid) {
                pcntl_waitpid($pid, $status);
                $this->assertSame(0, pcntl_wexitstatus($status));
            }
            $values = array_map(fn ($file) => (int) file_get_contents($file), $files);
            foreach ($files as $file) {
                unlink($file);
            }
            $this->assertSame($maximum, count(array_filter($values, fn ($n) => $n === 0)));
            $this->assertSame(4, count(array_filter($values, fn ($n) => $n === 429)));
            $key = 'chatbot:'.$domain.':'.app(SessionIdentity::class)->hash('synthetic', $domain);
            $ttl = Redis::connection('chatbot')->pttl($key);
            $this->assertGreaterThan(0, $ttl);
            $this->assertLessThanOrEqual(60000, $ttl);
        }
        DB::beginTransaction();
    }

    public function test_ip_budget_and_explicit_admin_proxy_configuration(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.10'])->postJson('/admin/login', ['email' => 'account'.$i.'@example.test', 'password' => 'wrong'], ['X-Forwarded-For' => '198.51.100.'.$i])->assertStatus(422);
        }
        $this->postJson('/admin/login', ['email' => 'other@example.test', 'password' => 'wrong'], ['X-Forwarded-For' => '203.0.113.1'])->assertStatus(429);
        config(['chatbot.trusted_proxies' => ['192.0.2.10']]);
        $this->postJson('/admin/login', ['email' => 'trusted@example.test', 'password' => 'wrong'], ['X-Forwarded-For' => '198.51.100.100'])->assertStatus(422);
        $key = 'chatbot:admin-login-ip:'.app(SessionIdentity::class)->hash('198.51.100.100', 'admin-login-ip');
        $this->assertSame('1', (string) Redis::connection('chatbot')->get($key));
        config(['chatbot.trusted_proxies' => ['*']]);
        $this->postJson('/admin/login')->assertStatus(503);
    }
}
