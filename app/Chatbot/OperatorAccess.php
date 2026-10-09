<?php

namespace App\Chatbot;

use Illuminate\Support\Facades\Log;

class OperatorAccess
{
    public function identity(): string
    {
        $user = function_exists('posix_getpwuid') ? (posix_getpwuid(posix_geteuid())['name'] ?? '') : '';
        if ($user === '' || ! in_array($user, config('chatbot.operator_users', []), true)) {
            throw new \RuntimeException('Operator console access is not configured for this OS account.');
        }

        return $user;
    }

    public function audit(string $action, ?int $id, string $outcome): void
    {
        Log::channel('chatbot_audit')->info('chatbot_operator', ['operator' => $this->identity(), 'action' => $action, 'faq_id' => $id, 'outcome' => $outcome, 'time' => now('UTC')->toISOString()]);
    }

    public static function escape(string $text): string
    {
        // JSON escaping neutralizes controls, ANSI/OSC escapes, newlines and format characters.
        $text = json_encode($text, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $text = preg_replace_callback('/[\p{Cf}\x{007F}-\x{009F}]/u', fn ($m) => sprintf('\\u%04x', mb_ord($m[0], 'UTF-8')), $text);

        return str_replace(['<', '>'], ['\\u003c', '\\u003e'], $text);
    }
}
