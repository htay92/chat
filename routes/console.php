<?php

use App\Chatbot\FaqConsole;
use App\Chatbot\OperatorAccess;
use App\Chatbot\QuestionRetention;
use App\Chatbot\QuestionReview;
use App\Chatbot\SessionIdentity;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

foreach (['create', 'update', 'activate', 'deactivate', 'list', 'show'] as $action) {
    $signature = 'chatbot:faq:'.$action.(in_array($action, ['update', 'activate', 'deactivate', 'show']) ? ' {faq}' : '').(in_array($action, ['create', 'update']) ? ' {--stdin : Read question/answer JSON from protected stdin}' : '');
    Artisan::command($signature, function () use ($action) {
        return app(FaqConsole::class)->run($this, $action);
    })->purpose('Restricted operator-only prepared FAQ '.$action);
}

Artisan::command('chatbot:questions:review {--limit=50} {--cursor=}', function () {
    return app(QuestionReview::class)->run($this);
})->purpose('Restricted read-only unanswered question review');

Artisan::command('chatbot:questions:purge', function () {
    try {
        $count = app(QuestionRetention::class)->purge();
        Log::info('chatbot_purge_completed', ['count' => $count, 'time' => now('UTC')->toISOString()]);
        $this->info('Purged '.$count.' questions.');

        return 0;
    } catch (Throwable) {
        Log::error('chatbot_purge_failed');
        $this->error('Question purge failed; inspect service health and retry.');

        return 1;
    }
})->purpose('Scheduled bounded question retention purge');

Artisan::command('chatbot:questions:delete-session', function () {
    try {
        $access = app(OperatorAccess::class);
        $access->identity();
        $uuid = $this->secret('Session UUID (protected input)');
        if (! SessionIdentity::valid($uuid)) {
            throw new InvalidArgumentException;
        }
        if (! $this->confirm('Permanently delete questions for this session?')) {
            return 0;
        }
        $access->audit('delete-session', null, 'started');
        $count = app(QuestionRetention::class)->deleteSession($uuid);
        $access->audit('delete-session', null, 'completed');
        $this->info('Deleted '.$count.' questions. Restore-time deletion instructions must be maintained in restricted backup operations.');

        return 0;
    } catch (Throwable) {
        $this->error('Session deletion failed. Check operator access, protected input and service health.');

        return 1;
    }
})->purpose('Restricted operator-only early deletion by protected session input');

Schedule::command('chatbot:questions:purge')->daily()->timezone('UTC')->withoutOverlapping()->onFailure(function () {
    Log::error('chatbot_scheduled_purge_failed');
});
