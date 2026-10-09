<?php

use App\Chatbot\ChatbotFailure;
use App\Http\Middleware\ChatbotBoundary;
use App\Http\Middleware\ChatbotTrustProxies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(ChatbotBoundary::class);
        $middleware->replace(TrustProxies::class, ChatbotTrustProxies::class);
        $middleware->trimStrings(except: [fn (Request $request) => ChatbotBoundary::applies($request)]);
        $middleware->convertEmptyStringsToNull(except: [fn (Request $request) => ChatbotBoundary::applies($request)]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (Throwable $e) {
            if (app()->bound('request') && ChatbotBoundary::applies(request())) {
                Log::warning('chatbot_request_failed', ['status' => ChatbotFailure::response($e)->getStatusCode()]);

                return false;
            }
        });
        $exceptions->render(function (Throwable $e, Request $request) {
            if (ChatbotBoundary::applies($request)) {
                return ChatbotFailure::response($e);
            }
        });
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
