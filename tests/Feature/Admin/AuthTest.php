<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Redis;
use Tests\Support\AdminTestCase;

class AuthTest extends AdminTestCase
{
    public function test_login_rejects_extra_fields_and_clears_previous_password_confirmation(): void
    {
        $admin = $this->admin();
        $this->postJson('/admin/login', ['email' => $admin->email, 'password' => 'a-safe-password', 'is_chatbot_admin' => true])->assertStatus(422);
        $this->assertGuest();
        $this->withSession(['admin_password_confirmed' => now()->timestamp])->postJson('/admin/login', ['email' => $admin->email, 'password' => 'a-safe-password'])->assertOk()->assertSessionMissing('admin_password_confirmed');
    }

    public function test_non_admin_cannot_sign_in_or_read_or_mutate_existing_and_missing_resources(): void
    {
        $user = User::create(['name' => 'Visitor', 'email' => 'visitor@example.test', 'password' => 'a-safe-password']);
        $this->postJson('/admin/login', ['email' => $user->email, 'password' => 'a-safe-password'])->assertStatus(422);
        $this->assertGuest();
        $this->actingAs($user);
        foreach (['/admin/chatbot/questions', '/admin/chatbot/questions/999', '/admin/chatbot/faqs', '/admin/chatbot/faqs/create', '/admin/chatbot/faqs/999', '/admin/chatbot/faqs/999/edit'] as $url) {
            $this->get($url)->assertForbidden();
        }
        foreach ([['GET', '/admin/chatbot'], ['GET', '/admin/chatbot/data/metrics'], ['POST', '/admin/chatbot/data/questions/search'], ['POST', '/admin/chatbot/data/faqs/search'], ['GET', '/admin/chatbot/data/questions/999'], ['GET', '/admin/chatbot/data/faqs/999'], ['POST', '/admin/chatbot/data/faqs'], ['PATCH', '/admin/chatbot/data/faqs/999'], ['POST', '/admin/chatbot/data/faqs/999/activation'], ['DELETE', '/admin/chatbot/data/faqs/999'], ['DELETE', '/admin/chatbot/data/questions/999'], ['POST', '/admin/chatbot/password/confirm']] as [$method, $url]) {
            $this->json($method, $url)->assertForbidden()->assertHeader('Cache-Control', 'no-store, private');
        }
    }

    protected function admin(): User
    {
        $user = User::create(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'a-safe-password']);
        $user->is_chatbot_admin = true;
        $user->save();

        return $user;
    }

    public function test_login_guest_forbidden_revocation_and_logout(): void
    {
        $this->get('/admin/chatbot')->assertRedirect('/admin/login');
        $this->getJson('/admin/chatbot/data/metrics')->assertUnauthorized();
        foreach ([['GET', '/admin/chatbot/questions'], ['GET', '/admin/chatbot/faqs'], ['GET', '/admin/chatbot/faqs/999/edit'], ['POST', '/admin/chatbot/data/questions/search'], ['POST', '/admin/chatbot/data/faqs/search'], ['GET', '/admin/chatbot/data/questions/999'], ['GET', '/admin/chatbot/data/faqs/999'], ['POST', '/admin/chatbot/data/faqs'], ['PATCH', '/admin/chatbot/data/faqs/999'], ['POST', '/admin/chatbot/data/faqs/999/activation'], ['DELETE', '/admin/chatbot/data/faqs/999'], ['DELETE', '/admin/chatbot/data/questions/999'], ['POST', '/admin/chatbot/password/confirm']] as [$method, $url]) {
            $this->json($method, $url)->assertUnauthorized();
        }
        $admin = $this->admin();
        $this->postJson('/admin/login', ['email' => $admin->email, 'password' => 'wrong'])->assertStatus(422)->assertDontSee('wrong');
        $this->postJson('/admin/login', ['email' => $admin->email, 'password' => 'a-safe-password'])->assertOk();
        $this->assertAuthenticatedAs($admin);
        $this->get('/admin/chatbot')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $admin->is_chatbot_admin = false;
        $admin->save();
        $this->getJson('/admin/chatbot/data/metrics')->assertForbidden();
        $this->postJson('/admin/logout')->assertNoContent();
        $this->assertGuest();
    }

    public function test_real_csrf_middleware_not_testing_bypass(): void
    {
        $this->app->bind(PreventRequestForgery::class, fn ($app) => new class($app, $app['encrypter']) extends PreventRequestForgery
        {
            protected function runningUnitTests()
            {
                return false;
            }
        });
        $this->postJson('/admin/login', ['email' => 'x@example.test', 'password' => 'secret'])->assertStatus(419);
        $this->withSession(['_token' => 'synthetic-token'])->postJson('/admin/login', ['email' => 'x@example.test', 'password' => 'secret'], ['X-CSRF-TOKEN' => 'synthetic-token'])->assertStatus(422);
        $this->actingAs($this->admin())->withSession(['admin_started' => now()->timestamp, 'admin_activity' => now()->timestamp]);
        $this->postJson('/admin/chatbot/data/questions/search', [], ['X-CSRF-TOKEN' => 'synthetic-token'])->assertOk();
        $this->postJson('/admin/chatbot/data/faqs', ['question' => 'CSRF question', 'answer' => 'Prepared'], ['X-CSRF-TOKEN' => 'synthetic-token'])->assertCreated();
        foreach ([['POST', '/admin/logout'], ['POST', '/admin/chatbot/data/questions/search'], ['POST', '/admin/chatbot/data/faqs'], ['PATCH', '/admin/chatbot/data/faqs/999'], ['POST', '/admin/chatbot/data/faqs/999/activation'], ['DELETE', '/admin/chatbot/data/faqs/999'], ['DELETE', '/admin/chatbot/data/questions/999'], ['POST', '/admin/chatbot/password/confirm']] as [$method, $url]) {
            $this->json($method, $url, [], ['X-CSRF-TOKEN' => 'incorrect'])->assertStatus(419);
        }
    }

    public function test_idle_and_absolute_expiration(): void
    {
        $admin = $this->admin();
        foreach ([['admin_started' => now()->timestamp, 'admin_activity' => now()->timestamp - 1800], ['admin_started' => now()->timestamp - 28800, 'admin_activity' => now()->timestamp]] as $times) {
            $this->actingAs($admin)->withSession($times)->getJson('/admin/chatbot/data/metrics')->assertUnauthorized();
        }
    }

    public function test_login_quota_and_redis_failure(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/admin/login', ['email' => 'missing@example.test', 'password' => 'incorrect'])->assertStatus(422);
        }
        $this->postJson('/admin/login', ['email' => 'missing@example.test', 'password' => 'incorrect'])->assertStatus(429)->assertHeader('Retry-After');
        config(['database.redis.chatbot.port' => 6399]);
        Redis::purge('chatbot');
        $this->app->forgetInstance('redis');
        Redis::clearResolvedInstance('redis');
        $this->postJson('/admin/login', ['email' => 'other@example.test', 'password' => 'incorrect'])->assertStatus(503);
    }
}
