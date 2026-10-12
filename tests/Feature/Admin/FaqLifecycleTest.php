<?php

namespace Tests\Feature\Admin;

use App\Chatbot\FaqService;
use App\Chatbot\QuestionAnswerService;
use App\Models\ChatbotQuestion;
use App\Models\Faq;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\Support\AdminTestCase;

class FaqLifecycleTest extends AdminTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $user = User::create(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'a-safe-password']);
        $user->is_chatbot_admin = true;
        $user->save();
        $this->actingAs($user)->withSession(['admin_started' => now()->timestamp, 'admin_activity' => now()->timestamp]);
    }

    public function test_faq_delete_recent_password_revision_and_original_history(): void
    {
        $s = app(FaqService::class);
        $f = $s->create('Hello', 'Answer');
        $f = $s->activate($f->id, $s->revision($f), true);
        $sid = '11111111-1111-4111-8111-111111111111';
        $rid = '22222222-2222-4222-8222-222222222222';
        app(QuestionAnswerService::class)->submit('Hello', $sid, $rid);
        $q = ChatbotQuestion::firstOrFail();
        $before = $q->getAttributes();
        $url = '/admin/chatbot/data/faqs/'.$f->id;
        $this->deleteJson($url, ['revision' => $s->revision($f), 'confirmed' => true])->assertStatus(428);
        $this->postJson('/admin/chatbot/password/confirm', ['password' => 'a-safe-password'])->assertNoContent();
        $this->deleteJson($url, ['revision' => str_repeat('a', 64), 'confirmed' => true])->assertStatus(409);
        $this->deleteJson($url, ['revision' => $s->revision($f), 'confirmed' => false])->assertStatus(422);
        $this->deleteJson($url, ['revision' => $s->revision($f), 'confirmed' => true])->assertNoContent();
        $before['faq_id'] = null;
        $this->assertSame($before, $q->fresh()->getAttributes());
        $reply = app(QuestionAnswerService::class)->submit('Hello', $sid, $rid);
        $this->assertSame('answered', $reply['status']);
        $this->assertSame('fallback', $reply['reply_source']);
    }

    public function test_independent_draft_and_disappearing_source(): void
    {
        $q = ChatbotQuestion::create(['question' => 'Visitor question', 'status' => 'unanswered', 'session_hash' => str_repeat('a', 64), 'request_id' => (string) Str::uuid()]);
        $before = $q->fresh()->getAttributes();
        $this->get('/admin/chatbot/faqs/create?source_question_id='.$q->id)->assertOk();
        $this->assertSame(0, Faq::count());
        $f = $this->postJson('/admin/chatbot/data/faqs', ['question' => $q->question, 'answer' => 'Prepared', 'source_question_id' => $q->id])->assertCreated()->assertJsonPath('data.is_active', false)->json('data');
        $this->postJson('/admin/chatbot/data/faqs/'.$f['id'].'/activation', ['is_active' => true, 'revision' => $f['revision'], 'confirmed' => true])->assertOk();
        $this->assertSame($before, $q->fresh()->getAttributes());
        $q->delete();
        $this->postJson('/admin/chatbot/data/faqs', ['question' => 'Other', 'answer' => 'Prepared', 'source_question_id' => $q->id])->assertNotFound();
    }
}
