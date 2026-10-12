<?php

namespace Tests\Feature\Admin;

use App\Chatbot\FaqService;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\Support\AdminTestCase;

class AdminSecurityTest extends AdminTestCase
{
    public function test_content_free_audits_and_failure_reports(): void
    {
        $user = User::create(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'a-safe-password']);
        $user->is_chatbot_admin = true;
        $user->save();
        $this->actingAs($user)->withSession(['admin_started' => now()->timestamp, 'admin_activity' => now()->timestamp]);
        Log::spy();
        Log::shouldReceive('channel')->with('chatbot_audit')->andReturnSelf();
        $this->postJson('/admin/chatbot/data/faqs', ['question' => 'Sensitive synthetic text', 'answer' => '<script>literal</script>'])->assertCreated();
        Log::shouldHaveReceived('info')->with('chatbot_admin', Mockery::on(function ($metadata) use ($user) {
            $this->assertSame(['actor_id', 'action', 'resource_id', 'outcome', 'time'], array_keys($metadata));
            $this->assertSame($user->id, $metadata['actor_id']);
            $this->assertStringNotContainsString('Sensitive', json_encode($metadata));

            return true;
        }));
        $service = Mockery::mock(FaqService::class);
        $service->shouldReceive('create')->once()->andThrow(new \RuntimeException('Sensitive synthetic exception with SQL and payload'));
        $this->app->instance(FaqService::class, $service);
        $this->postJson('/admin/chatbot/data/faqs', ['question' => 'Sensitive synthetic text', 'answer' => 'Private'])->assertStatus(500)->assertDontSee('Sensitive')->assertDontSee('SQL');
        Log::shouldHaveReceived('warning')->with('chatbot_admin_request_failed', ['event' => 'request_failure']);
    }

    public function test_login_and_logout_regenerate_session_identity_and_tokens(): void
    {
        $user = User::create(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'a-safe-password']);
        $user->is_chatbot_admin = true;
        $user->save();
        $this->withSession(['probe' => 'synthetic']);
        $before = session()->getId();
        $token = session()->token();
        $this->postJson('/admin/login', ['email' => $user->email, 'password' => 'a-safe-password'])->assertOk();
        $this->assertNotSame($before, session()->getId());
        $this->assertNotSame($token, session()->token());
        $authenticated = session()->getId();
        $token = session()->token();
        $this->postJson('/admin/logout')->assertNoContent()->assertSessionMissing('probe');
        $this->assertGuest();
        $this->assertNotSame($authenticated, session()->getId());
        $this->assertNotSame($token, session()->token());
    }
}
