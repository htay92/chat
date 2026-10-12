<?php

namespace App\Http\Middleware;

use App\Chatbot\Admin\AdminFailure;
use Closure;
use Illuminate\Http\Request;

class AdminBoundary
{
    public static function applies(Request $request): bool
    {
        return $request->is('admin/login', 'admin/logout', 'admin/chatbot', 'admin/chatbot/*');
    }

    public function handle(Request $request, Closure $next)
    {
        if (! self::applies($request)) {
            return $next($request);
        }
        try {
            if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
                $length = $request->server('CONTENT_LENGTH');
                if ($length !== null && (! ctype_digit((string) $length) || (float) $length > 32768)) {
                    abort(413);
                }
                $raw = stream_get_contents($request->getContent(true), 32769);
                if ($raw === false || strlen($raw) > 32768) {
                    abort(413);
                }
            }
            $response = $next($request);
        } catch (\Throwable $e) {
            $response = AdminFailure::response($e, $request);
        }
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }
}
