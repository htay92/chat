<?php

namespace Tests\Unit\Chatbot;

use App\Http\Middleware\ChatbotBoundary;
use Illuminate\Http\Request;
use Tests\TestCase;

class BodyBoundaryTest extends TestCase
{
    public function test_bounded_stream_read_precedes_downstream_parsing(): void
    {
        foreach ([16384 => 200, 16385 => 413, 20000 => 413] as $length => $status) {
            $stream = fopen('php://temp', 'r+');
            fwrite($stream, str_repeat('x', $length));
            rewind($stream);
            $request = Request::create('/api/chatbot/questions', 'POST', [], [], [], [], $stream);
            $called = false;
            $response = (new ChatbotBoundary)->handle($request, function ($request) use (&$called) {
                $called = true;
                $this->assertSame(16384, strlen($request->attributes->get('chatbot_body')));

                return response()->json(['ok' => true]);
            });
            $this->assertSame($status, $response->getStatusCode());
            $this->assertSame($status === 200, $called);
            $this->assertLessThanOrEqual(16385, ftell($stream));
            fclose($stream);
        }
    }
}
