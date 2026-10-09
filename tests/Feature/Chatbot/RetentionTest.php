<?php

namespace Tests\Feature\Chatbot;

use App\Chatbot\QuestionAnswerService;
use App\Chatbot\SessionIdentity;
use App\Models\ChatbotQuestion;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Tests\Support\ChatbotTestCase;

class RetentionTest extends ChatbotTestCase
{
    public function test_purge_deletes_at_cutoff_in_multiple_batches_without_touching_newer_rows(): void
    {
        $this->travelTo(Carbon::parse('2026-10-09 12:00:00', 'UTC'));
        $cutoff = now('UTC')->subDays(90);
        $rows = [];
        for ($i = 0; $i < 501; $i++) {
            $rows[] = ['session_hash' => str_repeat('a', 64), 'request_id' => sprintf('00000000-0000-4000-8000-%012d', $i), 'question' => 'Old', 'status' => $i % 2 ? 'answered' : 'unanswered', 'created_at' => $cutoff, 'updated_at' => now()];
        }
        ChatbotQuestion::insert($rows);
        ChatbotQuestion::create(['session_hash' => str_repeat('a', 64), 'request_id' => '99999999-9999-4999-8999-999999999999', 'question' => 'Newer', 'status' => 'unanswered', 'created_at' => $cutoff->copy()->addSecond()]);
        $this->artisan('chatbot:questions:purge')->expectsOutputToContain('Purged 501 questions.')->assertSuccessful();
        $this->assertSame(1, ChatbotQuestion::count());
        $this->artisan('chatbot:questions:purge')->expectsOutputToContain('Purged 0 questions.')->assertSuccessful();
        $this->travelBack();
    }

    public function test_operator_deletion_is_exact_and_removes_deduplication(): void
    {
        $s = '00000000-0000-4000-8000-000000000001';
        $r = '00000000-0000-4000-8000-000000000002';
        $service = app(QuestionAnswerService::class);
        $service->submit('Hi', $s, $r);
        $service->submit('Hi', '00000000-0000-4000-8000-000000000003', $r);
        $this->artisan('chatbot:questions:delete-session')->expectsQuestion('Session UUID (protected input)', $s)->expectsConfirmation('Permanently delete questions for this session?', 'yes')->assertSuccessful();
        $this->assertSame(1, ChatbotQuestion::count());
        $this->assertSame(0, ChatbotQuestion::where('session_hash', app(SessionIdentity::class)->hash($s))->count());
        $this->assertFalse($service->submit('Hi', $s, $r)['replayed']);
    }

    public function test_daily_schedule_prevents_overlap_and_command_access_is_restricted(): void
    {
        $events = collect(app(Schedule::class)->events())->filter(fn ($event) => str_contains($event->command ?? '', 'chatbot:questions:purge'));
        $this->assertCount(1, $events);
        $this->assertSame('0 0 * * *', $events->first()->expression);
        $this->assertTrue($events->first()->withoutOverlapping);
        config(['chatbot.operator_users' => []]);
        $this->artisan('chatbot:questions:delete-session')->assertFailed();
    }
}
