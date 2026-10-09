<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;

class ChatbotTrustProxies extends TrustProxies
{
    public function handle(Request $request, Closure $next)
    {
        if (! ChatbotBoundary::applies($request)) {
            return parent::handle($request, $next);
        }
        $proxies = config('chatbot.trusted_proxies', []);
        foreach ($proxies as $proxy) {
            $parts = explode('/', $proxy);
            $ip = $parts[0];
            $maxBits = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? 128 : 32;
            if (! filter_var($ip, FILTER_VALIDATE_IP) || count($parts) > 2
                || (isset($parts[1]) && (! ctype_digit($parts[1]) || (int) $parts[1] < 1 || (int) $parts[1] > $maxBits))) {
                abort(503);
            }
        }
        // No provider/Host-derived implicit trust; only the expected header mask.
        Request::setTrustedProxies($proxies, Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PROTO | Request::HEADER_X_FORWARDED_PORT);

        return $next($request);
    }
}
