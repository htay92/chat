<?php

namespace Tests\Unit\Chatbot;

use App\Http\Middleware\AdminBoundary;
use Illuminate\Http\Request;
use Tests\TestCase;

class AdminBoundaryTest extends TestCase
{
    public function test_body_stream_is_bounded_before_parsing_and_safe_errors_are_private(): void
    {
        foreach ([32768 => 200, 32769 => 413] as $length => $status) {
            foreach ([null, '1'] as $claimed) {
                $stream = fopen('php://temp', 'r+');
                fwrite($stream, str_repeat('x', $length));
                rewind($stream);
                $request = Request::create('/admin/login', 'POST', [], [], [], ['HTTP_ACCEPT' => 'application/json'], $stream);
                if ($claimed !== null) {
                    $request->server->set('CONTENT_LENGTH', $claimed);
                }
                $called = false;
                $response = (new AdminBoundary)->handle($request, function () use (&$called) {
                    $called = true;

                    return response()->json(['ok' => true]);
                });
                $this->assertSame($status, $response->getStatusCode());
                $this->assertSame($status === 200, $called);
                $this->assertLessThanOrEqual(32769, ftell($stream));
                $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
                fclose($stream);
            }
        }
    }
}
