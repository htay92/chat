<?php

namespace App\Chatbot;

use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ChatbotFailure
{
    public static function response(\Throwable $e)
    {
        $status = match (true) {
            $e instanceof ValidationException => 422,
            $e instanceof Conflict => 409,
            $e instanceof QueryException => 503,
            $e instanceof HttpExceptionInterface => $e->getStatusCode(),
            default => 500,
        };
        $body = ['message' => match ($status) {
            422 => 'Invalid question request.', 429 => 'Too many questions. Please wait.', 503 => 'Question service temporarily unavailable.', default => 'Question request could not be completed.'
        }];
        if ($e instanceof ValidationException) {
            $body['errors'] = $e->errors();
        }
        $headers = $e instanceof HttpExceptionInterface ? $e->getHeaders() : [];
        $headers['Cache-Control'] = 'no-store';

        return response()->json($body, $status, $headers);
    }
}
