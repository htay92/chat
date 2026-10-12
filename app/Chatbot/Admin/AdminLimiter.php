<?php

namespace App\Chatbot\Admin;

use App\Chatbot\AtomicQuestionLimiter;
use App\Chatbot\SessionIdentity;

class AdminLimiter
{
    public function acquire(string $domain, string $identity, int $maximum): void
    {
        try {
            $key = 'chatbot:'.$domain.':'.app(SessionIdentity::class)->hash($identity, $domain);
            $wait = app(AtomicQuestionLimiter::class)->acquire($key, $maximum);
        } catch (\Throwable) {
            abort(503);
        }
        if ($wait > 0) {
            abort(429, '', ['Retry-After' => $wait]);
        }
    }

    public function login(string $email, string $ip): void
    {
        $this->acquire('admin-login-ip', $ip, 20);
        $this->acquire('admin-login-account', mb_strtolower(trim($email)), 5);
    }
}
