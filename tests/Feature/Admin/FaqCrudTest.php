<?php

namespace Tests\Feature\Admin;

use App\Models\Faq;
use App\Models\User;
use Tests\Support\AdminTestCase;

class FaqCrudTest extends AdminTestCase
{
    public function test_shared_unicode_bounds_report_accessible_field_errors(): void
    {
        $this->postJson('/admin/chatbot/data/faqs', ['question' => 'Hi', 'answer' => str_repeat('界', 4001)])
            ->assertStatus(422)->assertJsonStructure(['errors' => ['answer']])->assertDontSee(str_repeat('界', 10));
        $this->postJson('/admin/chatbot/data/faqs', ['question' => "\u{FEFF}\u{0085}", 'answer' => 'A'])
            ->assertStatus(422)->assertJsonStructure(['errors' => ['question']]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::create(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'a-safe-password']);
        $user->is_chatbot_admin = true;
        $user->save();
        $this->actingAs($user)->withSession(['admin_started' => now()->timestamp, 'admin_activity' => now()->timestamp]);
    }

    public function test_shared_create_edit_activation_and_revision_contract(): void
    {
        $r = $this->postJson('/admin/chatbot/data/faqs', ['question' => " CAFE\u{0301} 東京? ", 'answer' => '<script>plain</script>'])->assertCreated()->assertJsonPath('data.is_active', false);
        $id = $r->json('data.id');
        $revision = $r->json('data.revision');
        $this->assertSame('café 東京?', Faq::findOrFail($id)->normalized_question);
        $this->postJson('/admin/chatbot/data/faqs', ['question' => 'café 東京?', 'answer' => 'duplicate'])->assertStatus(409)->assertJsonPath('code', 'duplicate_question');
        $active = $this->postJson('/admin/chatbot/data/faqs/'.$id.'/activation', ['revision' => $revision, 'is_active' => true, 'confirmed' => true])->assertOk()->assertJsonPath('data.is_active', true)->json('data');
        $noop = $this->patchJson('/admin/chatbot/data/faqs/'.$id, ['revision' => $active['revision'], 'question' => $active['question'], 'answer' => $active['answer']])->assertOk()->assertJsonPath('data.is_active', true)->json('data');
        $this->patchJson('/admin/chatbot/data/faqs/'.$id, ['revision' => $noop['revision'], 'question' => 'café 東京?', 'answer' => 'new'])->assertOk()->assertJsonPath('data.is_active', false);
        foreach ([['PATCH', '', ['question' => 'overwrite', 'answer' => 'bad']], ['POST', '/activation', ['is_active' => true, 'confirmed' => true]]] as [$method, $suffix, $data]) {
            $this->json($method, '/admin/chatbot/data/faqs/'.$id.$suffix, $data + ['revision' => $revision])->assertStatus(409)->assertJsonPath('code', 'stale_revision');
        }
        $this->assertSame('new', Faq::findOrFail($id)->answer);
        $this->postJson('/admin/chatbot/data/faqs/search', ['active' => 'inactive'])->assertJsonCount(1, 'data')->assertDontSee('match_key');
        $this->getJson('/admin/chatbot/data/faqs/'.$id)->assertOk()->assertDontSee('normalized_question');
    }

    public function test_validation_and_allowlists(): void
    {
        foreach ([['question' => '', 'answer' => 'Hi'], ['question' => [], 'answer' => 'Hi'], ['question' => 'Hi', 'answer' => str_repeat('a', 4001)], ['question' => 'Hi', 'answer' => 'A', 'is_active' => true], ['question' => 'Hi', 'answer' => 'A', 'is_chatbot_admin' => true]] as $data) {
            $this->postJson('/admin/chatbot/data/faqs', $data)->assertStatus(422);
        }
        $this->assertSame(0, Faq::count());
    }
}
