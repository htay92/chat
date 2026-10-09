<?php

namespace App\Chatbot;

use App\Models\ChatbotQuestion;
use App\Models\Faq;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class QuestionAnswerService
{
    public function submit(string $question, string $session, string $request): array
    {
        $question = app(FaqNormalizer::class)->question($question);
        if (! SessionIdentity::valid($session) || ! SessionIdentity::valid($request)) {
            throw new \InvalidArgumentException('Invalid identifiers.');
        }
        $hash = app(SessionIdentity::class)->hash($session);
        try {
            return DB::transaction(function () use ($question, $hash, $request) {
                $row = ChatbotQuestion::where('session_hash', $hash)->where('request_id', $request)->first();
                if ($row) {
                    return $this->replay($row, $question);
                }
                $faq = app(FaqService::class)->match($question);
                $row = ChatbotQuestion::create(['session_hash' => $hash, 'request_id' => $request, 'question' => $question, 'status' => $faq ? 'answered' : 'unanswered', 'faq_id' => $faq?->id]);

                return $this->reply($row, $faq, false);
            });
        } catch (UniqueConstraintViolationException $e) {
            // Recover only the submission-tuple constraint; never conceal unrelated SQL errors.
            if (! str_contains($e->getMessage(), 'chatbot_submission_unique')) {
                throw $e;
            }
            $row = ChatbotQuestion::where('session_hash', $hash)->where('request_id', $request)->first();
            if (! $row) {
                throw $e;
            }

            return $this->replay($row, $question);
        }
    }

    private function replay(ChatbotQuestion $row, string $question): array
    {
        if ($row->question !== $question) {
            throw new Conflict('Request identifier already used with different text.');
        }
        $faq = $row->status === 'answered'
            ? ($row->faq_id ? Faq::whereKey($row->faq_id)->where('is_active', true)->first() : null)
            : app(FaqService::class)->match($row->question);

        return $this->reply($row, $faq, true);
    }

    private function reply(ChatbotQuestion $row, ?Faq $faq, bool $replayed): array
    {
        return ['request_id' => $row->request_id, 'status' => $row->status, 'reply_source' => $faq ? 'faq' : 'fallback', 'answer' => $faq?->answer ?? config('chatbot.fallback'), 'replayed' => $replayed];
    }
}
