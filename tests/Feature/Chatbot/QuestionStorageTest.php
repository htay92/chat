<?php

namespace Tests\Feature\Chatbot;

use App\Chatbot\SessionIdentity;
use App\Models\ChatbotQuestion;
use App\Models\Faq;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Tests\Support\ChatbotTestCase;

class QuestionStorageTest extends ChatbotTestCase
{
    public function test_only_required_fields_and_hashed_identity_are_stored(): void
    {
        $hash = app(SessionIdentity::class)->hash('12345678-1234-4123-8123-123456789abc');
        $this->assertSame(64, strlen($hash));
        $this->assertStringNotContainsString('12345678', $hash);
        $this->assertEqualsCanonicalizing(['id', 'session_hash', 'request_id', 'question', 'status', 'faq_id', 'created_at', 'updated_at'], Schema::getColumnListing('chatbot_questions'));
        $q = ChatbotQuestion::create(['session_hash' => $hash, 'request_id' => '12345678-1234-4123-8123-123456789abc', 'question' => 'Hi', 'status' => 'unanswered']);
        $this->assertNull($q->faq_id);
        $this->assertSame('unanswered', $q->fresh()->status);
    }

    public function test_review_is_bounded_readonly_and_escapes_terminal_content(): void
    {
        for ($i = 0; $i < 52; $i++) {
            ChatbotQuestion::create(['session_hash' => str_repeat('a', 64), 'request_id' => sprintf('00000000-0000-4000-8000-%012d', $i), 'question' => "hello\033[31m<secret>", 'status' => 'unanswered']);
        }
        $this->artisan('chatbot:questions:review')->expectsOutputToContain('Next cursor:')->expectsOutputToContain('\\u001b')->assertSuccessful();
        $this->assertSame(52, ChatbotQuestion::where('status', 'unanswered')->count());
        $this->artisan('chatbot:questions:review', ['--limit' => 51])->assertFailed();
    }

    public function test_deleting_faq_keeps_original_answered_classification(): void
    {
        $f = Faq::create(['question' => 'Hi', 'answer' => 'Answer']);
        $q = ChatbotQuestion::create(['session_hash' => str_repeat('a', 64), 'request_id' => '00000000-0000-4000-8000-000000000001', 'question' => 'Hi', 'status' => 'answered', 'faq_id' => $f->id]);
        $f->delete();
        $this->assertNull($q->fresh()->faq_id);
        $this->assertSame('answered', $q->fresh()->status);
    }

    public function test_review_cursor_survives_purge_and_never_exposes_identifiers(): void
    {
        $rows = [];
        for ($i = 0; $i < 3; $i++) {
            $rows[] = ChatbotQuestion::create(['session_hash' => str_repeat('b', 64), 'request_id' => sprintf('00000000-0000-4000-8000-%012d', $i), 'question' => 'Question '.$i, 'status' => 'unanswered']);
        }
        $this->withoutMockingConsoleOutput();
        $this->assertSame(0, Artisan::call('chatbot:questions:review', ['--limit' => 2]));
        $output = Artisan::output();
        $this->assertStringNotContainsString(str_repeat('b', 64), $output);
        $this->assertStringNotContainsString('00000000-0000-4000', $output);
        preg_match('/Next cursor: (\S+)/', $output, $matches);
        $rows[1]->delete();
        $this->assertSame(0, Artisan::call('chatbot:questions:review', ['--limit' => 2, '--cursor' => $matches[1]]));
        $this->assertStringContainsString('Question 2', Artisan::output());
        $this->assertStringNotContainsString('Question 0', Artisan::output());
    }
}
