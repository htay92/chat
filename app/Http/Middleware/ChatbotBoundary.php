<?php

namespace App\Http\Middleware;

use App\Chatbot\ChatbotFailure;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ChatbotBoundary
{
    public static function applies(Request $request): bool
    {
        return $request->is('api/chatbot/questions');
    }

    public function handle(Request $request, Closure $next)
    {
        if (! self::applies($request)) {
            return $next($request);
        }
        try {
            if ($request->isMethod('POST')) {
                $length = $request->server('CONTENT_LENGTH');
                if ($length !== null && (! ctype_digit((string) $length) || (float) $length > 16384)) {
                    abort(413);
                }
                $stream = $request->getContent(true);
                $raw = stream_get_contents($stream, 16385);
                if ($raw === false || strlen($raw) > 16384) {
                    abort(413);
                }
                $request->attributes->set('chatbot_body', $raw);
            }
            $response = $next($request);
        } catch (\Throwable $e) {
            $response = ChatbotFailure::response($e);
            if ($response->getStatusCode() >= 500) {
                Log::warning('chatbot_request_failed', ['status' => $response->getStatusCode()]);
            }
        }
        $response->headers->set('Cache-Control', 'no-store');
        foreach (array_keys($response->headers->all()) as $header) {
            if (str_starts_with(strtolower($header), 'access-control-')) {
                $response->headers->remove($header);
            }
        }

        return $response;
    }
}
