<?php

namespace Tests\Feature\Chatbot;

use App\Chatbot\Conflict;
use App\Chatbot\FaqService;
use App\Chatbot\QuestionAnswerService;
use App\Models\ChatbotQuestion;
use Tests\Support\ChatbotTestCase;

class AnswerServiceTest extends ChatbotTestCase
{
    private string $session = '00000000-0000-4000-8000-000000000001';

    private string $request = '00000000-0000-4000-8000-000000000002';

    public function test_empty_inactive_newly_active_and_replay_preserve_original_status(): void
    {
        $service = app(QuestionAnswerService::class);
        $first = $service->submit('Hello', $this->session, $this->request);
        $this->assertSame('unanswered', $first['status']);
        $this->assertSame('fallback', $first['reply_source']);
        $this->assertFalse($first['replayed']);
        $row = ChatbotQuestion::firstOrFail();
        $before = $row->getAttributes();
        $fs = app(FaqService::class);
        $f = $fs->create('hello', 'Prepared');
        $this->assertSame('fallback', $service->submit('Hello', $this->session, $this->request)['reply_source']);
        $fs->activate($f->id, $fs->revision($f), true);
        $replay = $service->submit('Hello', $this->session, $this->request);
        $this->assertSame('unanswered', $replay['status']);
        $this->assertSame('faq', $replay['reply_source']);
        $this->assertSame('Prepared', $replay['answer']);
        $this->assertTrue($replay['replayed']);
        $this->assertSame($before, $row->fresh()->getAttributes());
        $fresh = $service->submit('Hello', $this->session, '00000000-0000-4000-8000-000000000003');
        $this->assertSame('answered', $fresh['status']);
        $this->assertSame(2, ChatbotQuestion::count());
    }

    public function test_answer_replay_after_edit_deactivate_and_delete(): void
    {
        $fs = app(FaqService::class);
        $f = $fs->create('Hi', 'First');
        $f = $fs->activate($f->id, $fs->revision($f), true);
        $s = app(QuestionAnswerService::class);
        $this->assertSame('First', $s->submit('Hi', $this->session, $this->request)['answer']);
        $f = $fs->update($f->id, $fs->revision($f), 'Hi', 'Edited');
        $replay = $s->submit('Hi', $this->session, $this->request);
        $this->assertSame('answered', $replay['status']);
        $this->assertSame('fallback', $replay['reply_source']);
        $f = $fs->activate($f->id, $fs->revision($f), true);
        $this->assertSame('Edited', $s->submit('Hi', $this->session, $this->request)['answer']);
        $f->delete();
        $this->assertSame('fallback', $s->submit('Hi', $this->session, $this->request)['reply_source']);
    }

    public function test_reused_request_with_different_text_conflicts(): void
    {
        $s = app(QuestionAnswerService::class);
        $s->submit('Hi', $this->session, $this->request);
        $this->expectException(Conflict::class);
        $s->submit('Changed', $this->session, $this->request);
    }

    public function test_failed_insert_does_not_leave_partial_collection(): void
    {
        ChatbotQuestion::creating(function () {
            throw new \RuntimeException('synthetic sensitive failure');
        });
        try {
            app(QuestionAnswerService::class)->submit('Hi', $this->session, $this->request);
            $this->fail('Failure hidden');
        } catch (\RuntimeException) {
            $this->assertSame(0, ChatbotQuestion::count());
        } finally {
            ChatbotQuestion::flushEventListeners();
            ChatbotQuestion::clearBootedModels();
        }
    }
}
