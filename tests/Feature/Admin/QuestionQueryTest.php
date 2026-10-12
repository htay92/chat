<?php

namespace Tests\Feature\Admin;

use App\Chatbot\FaqService;
use App\Models\ChatbotQuestion;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\Support\AdminTestCase;

class QuestionQueryTest extends AdminTestCase
{
    public function test_unicode_search_trims_before_bounds_and_page_size_has_strict_type(): void
    {
        $this->question(str_repeat('界', 200));
        $this->postJson('/admin/chatbot/data/questions/search', ['search' => "\u{FEFF}".str_repeat('界', 200)."\u{0085}"])->assertOk()->assertJsonCount(1, 'data');
        $this->postJson('/admin/chatbot/data/questions/search', ['per_page' => '25'])->assertStatus(422);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::create(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'a-safe-password']);
        $user->is_chatbot_admin = true;
        $user->save();
        $this->actingAs($user)->withSession(['admin_started' => now()->timestamp, 'admin_activity' => now()->timestamp]);
    }

    private function question(string $text): ChatbotQuestion
    {
        return ChatbotQuestion::create(['question' => $text, 'status' => 'unanswered', 'session_hash' => str_repeat('a', 64), 'request_id' => (string) Str::uuid()]);
    }

    public function test_metrics_and_whitelisted_details_without_mutation(): void
    {
        $this->getJson('/admin/chatbot/data/metrics')->assertOk()->assertExactJson(['data' => ['total_questions' => 0, 'originally_unanswered' => 0, 'active_faqs' => 0, 'inactive_faqs' => 0]]);
        $q = $this->question('<script>private</script>');
        $before = $q->fresh()->getAttributes();
        $faq = app(FaqService::class)->create('Hello', 'Prepared');
        $this->getJson('/admin/chatbot/data/metrics')->assertJsonPath('data.total_questions', 1)->assertJsonPath('data.originally_unanswered', 1)->assertJsonPath('data.inactive_faqs', 1);
        $response = $this->getJson('/admin/chatbot/data/questions/'.$q->id)->assertOk();
        $this->assertSame(['id', 'question', 'status', 'created_at', 'faq_id'], array_keys($response->json('data')));
        $response->assertDontSee('session_hash')->assertDontSee('request_id')->assertDontSee(str_repeat('a', 64));
        $this->assertSame($before, $q->fresh()->getAttributes());
    }

    public function test_literal_unicode_search_bounded_pagination_and_filters(): void
    {
        $target = $this->question('CAFÉ 100%_\\ literal');
        for ($i = 0; $i < 26; $i++) {
            $this->question('Other '.$i);
        }
        $response = $this->postJson('/admin/chatbot/data/questions/search', [])->assertOk();
        $this->assertCount(25, $response->json('data'));
        $cursor = $response->json('next_cursor');
        $this->assertNotNull($cursor);
        $target->delete();
        $next = $this->postJson('/admin/chatbot/data/questions/search', ['cursor' => $cursor])->assertOk();
        $this->assertCount(2, $next->json('data'));
        $this->question('CAFÉ 100%_\\ literal');
        $this->postJson('/admin/chatbot/data/questions/search', ['search' => 'café 100%_\\'])->assertJsonCount(1, 'data');
        $this->postJson('/admin/chatbot/data/questions/search', ['search' => 'cafe'])->assertJsonCount(0, 'data');
        $this->postJson('/admin/chatbot/data/questions/search', ['status' => 'answered'])->assertJsonCount(0, 'data');
        foreach ([['per_page' => 1000], ['status' => 'resolved'], ['cursor' => 'bad'], ['cursor' => '0'], ['search' => str_repeat('a', 201)], ['search' => []], ['sort' => 'question']] as $invalid) {
            $this->postJson('/admin/chatbot/data/questions/search', $invalid)->assertStatus(422);
        }
    }
}
