<?php

namespace App\Chatbot;

class SessionIdentity
{
    public static function valid(mixed $id): bool
    {
        return is_string($id) && preg_match('/\A[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/', $id) === 1;
    }

    public function hash(string $id, string $domain = 'session'): string
    {
        $key = config('chatbot.hmac_key');
        if (! is_string($key) || strlen($key) < 32) {
            throw new \RuntimeException('Chatbot identity key is not configured.');
        }

        return hash_hmac('sha256', $domain.':'.$id, $key);
    }
}
