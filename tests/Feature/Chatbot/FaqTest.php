<?php

namespace Tests\Feature\Chatbot;

use App\Chatbot\Conflict;
use App\Chatbot\FaqInput;
use App\Chatbot\FaqNormalizer;
use App\Chatbot\FaqService;
use App\Models\Faq;
use Database\Seeders\ChatbotDemoFaqSeeder;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Tests\Support\ChatbotTestCase;

class FaqTest extends ChatbotTestCase
{
    public function test_prepared_faq_workflow_and_revision_conflicts(): void
    {
        $service = app(FaqService::class);
        $faq = $service->create(' HELLO ', 'Prepared answer');
        $this->assertFalse($faq->is_active);
        $this->assertSame('hello', $faq->normalized_question);
        $faq = $service->activate($faq->id, $service->revision($faq), true);
        $this->assertTrue($faq->is_active);
        $stale = $service->revision($faq);
        $faq = $service->update($faq->id, $stale, 'hello', 'New answer');
        $this->assertFalse($faq->is_active);
        $this->expectException(Conflict::class);
        $service->activate($faq->id, $stale, true);
    }

    public function test_invalid_duplicate_and_noop_writes(): void
    {
        $s = app(FaqService::class);
        $f = $s->create('Hi', 'Answer');
        $s->activate($f->id, $s->revision($f), true);
        $f->refresh();
        $unchanged = $s->update($f->id, $s->revision($f), 'Hi', 'Answer');
        $this->assertTrue($unchanged->is_active);
        foreach ([[' hi ', 'Duplicate'], ['Other', str_repeat('a', 4001)], ['', 'Blank']] as [$q, $a]) {
            try {
                $s->create($q, $a);
                $this->fail('Invalid/duplicate accepted');
            } catch (Conflict|\InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
        $this->assertSame(1, Faq::count());
    }

    public function test_opt_in_seeder_does_not_overwrite_existing_content(): void
    {
        $s = app(FaqService::class);
        $f = $s->create('What is this chatbot?', 'Owner content');
        $this->seed(ChatbotDemoFaqSeeder::class);
        $this->seed(ChatbotDemoFaqSeeder::class);
        $this->assertSame('Owner content', $f->fresh()->answer);
        $this->assertSame(2, Faq::count());
        $this->assertSame(0, Faq::where('is_active', true)->count());
    }

    public function test_operator_commands_confirm_cancel_and_escape_output(): void
    {
        $this->artisan('chatbot:faq:create')
            ->expectsQuestion('Question (one line; for multiline use protected JSON stdin with --stdin)', 'Hi')
            ->expectsQuestion('Prepared answer', 'Answer')
            ->expectsConfirmation('Save inactive FAQ?', 'no')->assertSuccessful();
        $this->assertSame(0, Faq::count());
        $this->artisan('chatbot:faq:create')
            ->expectsQuestion('Question (one line; for multiline use protected JSON stdin with --stdin)', 'Hi')
            ->expectsQuestion('Prepared answer', 'Answer')
            ->expectsConfirmation('Save inactive FAQ?', 'yes')->assertSuccessful();
        $f = Faq::firstOrFail();
        $this->artisan('chatbot:faq:activate', ['faq' => $f->id])->expectsConfirmation('Activate FAQ?', 'yes')->assertSuccessful();
        $this->assertTrue($f->fresh()->is_active);
        $this->artisan('chatbot:faq:deactivate', ['faq' => $f->id])->expectsConfirmation('Deactivate FAQ?', 'yes')->assertSuccessful();
        $this->assertFalse($f->fresh()->is_active);
        $this->artisan('chatbot:faq:show', ['faq' => $f->id])->assertSuccessful();
        $this->artisan('chatbot:faq:list')->assertSuccessful();
    }

    public function test_exact_unicode_match_and_hash_collision_guard(): void
    {
        $service = app(FaqService::class);
        $f = $service->create("CAFE\u{0301}  東京?", 'Prepared');
        $service->activate($f->id, $service->revision($f), true);
        $this->assertSame($f->id, $service->match("café\u{0085}東京?")?->id);
        foreach (['cafe 東京?', 'café 東京', '東京?', 'prefix café 東京?'] as $miss) {
            $this->assertNull($service->match($miss));
        }
        app()->instance(FaqNormalizer::class, new class extends FaqNormalizer
        {
            public function digest(string $normalized): string
            {
                return str_repeat('0', 64);
            }
        });
        $collision = $service->create('Collision one', 'Answer');
        $service->activate($collision->id, $service->revision($collision), true);
        $this->assertNull($service->match('Collision two'));
        $this->expectException(Conflict::class);
        $service->create('Collision two', 'Answer');
    }

    public function test_command_edit_and_noop_activation_and_denied_access(): void
    {
        $service = app(FaqService::class);
        $f = $service->create('Hi', 'First');
        $f = $service->activate($f->id, $service->revision($f), true);
        $this->artisan('chatbot:faq:update', ['faq' => $f->id])
            ->expectsQuestion('Question (one line; for multiline use protected JSON stdin with --stdin)', 'Hi')
            ->expectsQuestion('Prepared answer', 'Edited')
            ->expectsConfirmation('Save changes (content edits become inactive)?', 'yes')->assertSuccessful();
        $this->assertSame('Edited', $f->fresh()->answer);
        $this->assertFalse($f->fresh()->is_active);
        $this->artisan('chatbot:faq:activate', ['faq' => $f->id])->expectsConfirmation('Activate FAQ?', 'yes')->assertSuccessful();
        $this->artisan('chatbot:faq:activate', ['faq' => $f->id])->expectsConfirmation('Activate FAQ?', 'yes')->assertSuccessful();
        $this->assertTrue($f->fresh()->is_active);
        config(['chatbot.operator_users' => []]);
        $this->artisan('chatbot:faq:list')->assertFailed();
        $this->artisan('chatbot:questions:review')->assertFailed();
    }

    public function test_operator_audit_contains_only_attributable_metadata(): void
    {
        $records = [];
        $logger = \Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('info')->twice()->withArgs(function ($message, $context) use (&$records) {
            $records[] = $context;

            return $message === 'chatbot_operator' && array_keys($context) === ['operator', 'action', 'faq_id', 'outcome', 'time'];
        });
        Log::shouldReceive('channel')->with('chatbot_audit')->andReturn($logger);
        $this->artisan('chatbot:faq:create')
            ->expectsQuestion('Question (one line; for multiline use protected JSON stdin with --stdin)', 'Private content')
            ->expectsQuestion('Prepared answer', 'Sensitive answer')
            ->expectsConfirmation('Save inactive FAQ?', 'yes')->assertSuccessful();
        $this->assertStringNotContainsString('Private content', json_encode($records));
        $this->assertStringNotContainsString('Sensitive answer', json_encode($records));
        $this->assertSame(posix_getpwuid(posix_geteuid())['name'], $records[0]['operator']);
    }

    public function test_multiline_stdin_cannot_supply_its_own_publication_confirmation(): void
    {
        $input = \Mockery::mock(FaqInput::class);
        $input->shouldReceive('read')->once()->andReturn(["Hi\nworld", "Prepared\nanswer"]);
        $input->shouldReceive('confirm')->once()->withArgs(fn ($command, $prompt) => $prompt === 'Save inactive FAQ?')->andReturn(true);
        app()->instance(FaqInput::class, $input);
        $this->artisan('chatbot:faq:create', ['--stdin' => true])->assertSuccessful();
        $this->assertSame("Hi\nworld", Faq::firstOrFail()->question);
        $this->assertFalse(Faq::firstOrFail()->is_active);
    }
}
