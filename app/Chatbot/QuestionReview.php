<?php

namespace App\Chatbot;

use App\Models\ChatbotQuestion;
use Illuminate\Console\Command;

class QuestionReview
{
    public function run(Command $command): int
    {
        try {
            $access = app(OperatorAccess::class);
            $access->identity();
            $access->audit('review', null, 'started');
            $limit = filter_var($command->option('limit'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 50]]) ?: throw new \InvalidArgumentException;
            $query = ChatbotQuestion::where('status', 'unanswered');
            if ($cursor = $command->option('cursor')) {
                $parts = json_decode(base64_decode($cursor, true) ?: '', true, 4, JSON_THROW_ON_ERROR);
                if (! is_array($parts) || count($parts) !== 2 || ! is_string($parts[0]) || ! preg_match('/\A\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\z/', $parts[0]) || ! is_int($parts[1]) || $parts[1] < 1) {
                    throw new \InvalidArgumentException;
                }
                $query->where(fn ($q) => $q->where('created_at', '>', $parts[0])->orWhere(fn ($q) => $q->where('created_at', $parts[0])->where('id', '>', $parts[1])));
            }
            $rows = $query->orderBy('created_at')->orderBy('id')->limit($limit + 1)->get(['id', 'created_at', 'question']);
            foreach ($rows->take($limit) as $row) {
                $command->line($row->id.' '.$row->created_at->utc()->format('Y-m-d H:i:s').' '.OperatorAccess::escape($row->question));
            }
            if ($rows->count() > $limit) {
                $last = $rows[$limit - 1];
                $command->line('Next cursor: '.base64_encode(json_encode([$last->created_at->format('Y-m-d H:i:s'), $last->id], JSON_THROW_ON_ERROR)));
            } elseif ($rows->isEmpty()) {
                $command->info('No unanswered questions.');
            }
            $access->audit('review', null, 'completed');

            return 0;
        } catch (\Throwable) {
            $command->error('Review failed. Check operator access and pagination input.');

            return 1;
        }
    }
}
