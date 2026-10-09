<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ChatbotOrigin
{
    public function handle(Request $request, Closure $next)
    {
        $origin = config('chatbot.origin');
        if (! is_string($origin) || $origin === '' || $request->headers->get('Origin') !== $origin) {
            abort(403);
        }
        if (strtolower(trim(explode(';', $request->headers->get('Content-Type', ''))[0])) !== 'application/json') {
            abort(415);
        }

        return $next($request);
    }
}
