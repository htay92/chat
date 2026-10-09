<?php

namespace App\Chatbot;

use App\Models\Faq;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class FaqService
{
    public function create(string $question, string $answer): Faq
    {
        try {
            return Faq::create(['question' => $question, 'answer' => $answer, 'is_active' => false]);
        } catch (UniqueConstraintViolationException) {
            throw new Conflict('FAQ key already exists.');
        }
    }

    public function revision(Faq $faq): string
    {
        return hash('sha256', json_encode([$faq->id, $faq->question, $faq->answer, $faq->is_active, $faq->updated_at?->toISOString()], JSON_THROW_ON_ERROR));
    }

    public function update(int $id, string $revision, string $question, string $answer): Faq
    {
        return $this->change($id, $revision, function (Faq $faq) use ($question, $answer) {
            $faq->question = $question;
            $faq->answer = $answer;
        });
    }

    public function activate(int $id, string $revision, bool $active): Faq
    {
        return $this->change($id, $revision, function (Faq $faq) use ($active) {
            $faq->is_active = $active;
        });
    }

    private function change(int $id, string $revision, callable $edit): Faq
    {
        try {
            return DB::transaction(function () use ($id, $revision, $edit) {
                $faq = Faq::lockForUpdate()->findOrFail($id);
                if (! hash_equals($this->revision($faq), $revision)) {
                    throw new Conflict('FAQ changed; reload before editing.');
                }
                $edit($faq);
                $faq->save();

                return $faq->fresh();
            });
        } catch (UniqueConstraintViolationException) {
            throw new Conflict('FAQ key already exists.');
        }
    }

    public function match(string $question): ?Faq
    {
        $n = app(FaqNormalizer::class);
        $text = $n->normalize($question);
        $faq = Faq::where('match_key', $n->digest($text))->where('is_active', true)->first();

        return $faq && $faq->normalized_question === $text ? $faq : null;
    }
}
