<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatbotQuestion extends Model
{
    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::saving(function (ChatbotQuestion $q) {
            if (! in_array($q->status, ['answered', 'unanswered'], true)) {
                throw new \InvalidArgumentException('Invalid classification.');
            }
            if ($q->exists && $q->isDirty()) {
                throw new \LogicException('Collected questions are immutable.');
            }
        });
    }
}
