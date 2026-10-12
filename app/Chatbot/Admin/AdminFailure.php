<?php

namespace App\Chatbot\Admin;

use App\Chatbot\Conflict;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class AdminFailure
{
    public static function response(\Throwable $e, Request $request)
    {
        $status = match (true) {
            $e instanceof AuthenticationException => 401,
            $e instanceof AuthorizationException => 403,
            $e instanceof TokenMismatchException => 419,
            $e instanceof ValidationException, $e instanceof \InvalidArgumentException => 422,
            $e instanceof Conflict => 409,
            $e instanceof ModelNotFoundException => 404,
            $e instanceof QueryException => 503,
            $e instanceof HttpExceptionInterface => $e->getStatusCode(),
            default => 500,
        };
        $code = match ($status) {
            401 => 'unauthenticated', 403 => 'forbidden', 404 => 'not_found', 409 => $e instanceof Conflict && str_contains($e->getMessage(), 'key') ? 'duplicate_question' : 'stale_revision',
            413 => 'body_too_large', 419 => 'csrf_expired', 422 => 'validation', 429 => 'rate_limited', 428 => 'password_required', default => 'service_error',
        };
        $errors = $e instanceof ValidationException ? $e->errors() : [];
        $headers = $e instanceof HttpExceptionInterface ? $e->getHeaders() : [];
        if ($status === 401 && ! $request->expectsJson()) {
            return redirect('/admin/login')->header('Cache-Control', 'no-store');
        }
        if (! $request->expectsJson()) {
            return response()->view('admin.login', ['message' => 'The request could not be completed. Please check your access or input.'], $status, ['Cache-Control' => 'no-store']);
        }

        return response()->json(['message' => 'The request could not be completed.', 'code' => $code, 'errors' => $errors], $status, $headers)->header('Cache-Control', 'no-store');
    }
}
