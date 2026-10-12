<?php

namespace Tests\Feature\Admin;

use App\Chatbot\FaqService;
use App\Models\ChatbotQuestion;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\Support\AdminTestCase;

class AdminPagesTest extends AdminTestCase
{
    public function test_private_page_shells_mount_only_admin_and_validate_resources(): void
    {
        $user = User::create(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'a-safe-password']);
        $user->is_chatbot_admin = true;
        $user->save();
        $this->actingAs($user)->withSession(['admin_started' => now()->timestamp, 'admin_activity' => now()->timestamp]);
        $faq = app(FaqService::class)->create('Prepared question', 'Prepared answer');
        $question = ChatbotQuestion::create(['question' => 'Private visitor text', 'status' => 'unanswered', 'session_hash' => str_repeat('a', 64), 'request_id' => (string) Str::uuid()]);
        foreach (['' => 'dashboard', '/questions' => 'questions', '/questions/'.$question->id => 'question-detail', '/faqs' => 'faqs', '/faqs/create' => 'faq-create', '/faqs/'.$faq->id => 'faq-detail', '/faqs/'.$faq->id.'/edit' => 'faq-edit'] as $path => $page) {
            $this->get('/admin/chatbot'.$path)->assertOk()->assertSee('data-chatbot-admin', false)->assertSee('data-page="'.$page.'"', false)
                ->assertDontSee('data-chatbot-widget')->assertDontSee('session_hash')->assertDontSee('request_id')->assertDontSee('Private visitor text')
                ->assertHeader('Cache-Control', 'no-store, private');
        }
        $this->get('/admin/chatbot/questions/999')->assertNotFound();
        $this->get('/admin/chatbot/faqs/999/edit')->assertNotFound();
        $this->get('/')->assertOk()->assertSee('data-chatbot-widget', false)->assertDontSee('data-chatbot-admin');
    }
}
