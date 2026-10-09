<?php

namespace App\Http\Middleware;

use App\Chatbot\AtomicQuestionLimiter;
use App\Chatbot\SessionIdentity;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ChatbotRateLimit
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $identity = app(SessionIdentity::class);
            $limiter = app(AtomicQuestionLimiter::class);
            $wait = $limiter->acquire('chatbot:ip:'.$identity->hash($request->ip() ?? 'unknown', 'ip'), 30);
            if ($wait) {
                throw new HttpException(429, '', null, ['Retry-After' => (string) $wait]);
            }
            $data = json_decode($request->attributes->get('chatbot_body', ''), true, 8);
            if (is_array($data) && SessionIdentity::valid($data['session_id'] ?? null)) {
                $wait = $limiter->acquire('chatbot:session:'.$identity->hash($data['session_id']), 10);
                if ($wait) {
                    throw new HttpException(429, '', null, ['Retry-After' => (string) $wait]);
                }
            }
        } catch (HttpException $e) {
            throw $e;
        } catch (\Throwable) {
            abort(503);
        }

        return $next($request);
    }
}
