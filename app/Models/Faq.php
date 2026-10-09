<?php

namespace App\Models;

use App\Chatbot\FaqNormalizer;
use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    protected $guarded = ['id', 'normalized_question', 'match_key'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(function (Faq $faq) {
            $n = app(FaqNormalizer::class);
            $faq->question = $n->question($faq->question);
            $faq->answer = $n->answer($faq->answer);
            $faq->normalized_question = $n->normalize($faq->question);
            $faq->match_key = $n->digest($faq->normalized_question);
            if ($faq->exists && ($faq->isDirty('question') || $faq->isDirty('answer'))) {
                $faq->is_active = false;
            }
        });
    }
}
