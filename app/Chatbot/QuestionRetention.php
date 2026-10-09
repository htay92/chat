<?php

namespace App\Chatbot;

use App\Models\ChatbotQuestion;

class QuestionRetention
{
    public function purge(): int
    {
        $cutoff = now('UTC')->subDays(config('chatbot.retention_days'));

        return $this->deleteBatches(fn () => ChatbotQuestion::where('created_at', '<=', $cutoff)->orderBy('created_at')->orderBy('id'));
    }

    public function deleteSession(string $uuid): int
    {
        if (! SessionIdentity::valid($uuid)) {
            throw new \InvalidArgumentException('Invalid session UUID.');
        }
        $hash = app(SessionIdentity::class)->hash($uuid);

        return $this->deleteBatches(fn () => ChatbotQuestion::where('session_hash', $hash)->orderBy('id'));
    }

    private function deleteBatches(callable $query): int
    {
        $total = 0;
        do {
            $ids = $query()->limit(500)->pluck('id');
            if ($ids->isEmpty()) {
                break;
            }
            $total += ChatbotQuestion::whereIn('id', $ids)->delete();
        } while ($ids->count() === 500);

        return $total;
    }
}
