<?php

namespace Tests\Feature\Chatbot;

use App\Chatbot\QuestionAnswerService;
use App\Models\ChatbotQuestion;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Tests\Support\ChatbotTestCase;

class ApiTest extends ChatbotTestCase
{
    private function payload(): array
    {
        return ['question' => ' Hi ', 'session_id' => '00000000-0000-4000-8000-000000000001', 'request_id' => '00000000-0000-4000-8000-000000000002'];
    }

    private function send(array $data, array $headers = [])
    {
        return $this->postJson('/api/chatbot/questions', $data, array_replace(['Origin' => 'http://localhost:9000'], $headers));
    }

    public function test_commit_reply_and_replay_without_cookies_or_private_fields(): void
    {
        $this->send($this->payload())->assertCreated()->assertJsonPath('data.status', 'unanswered')->assertJsonPath('data.reply_source', 'fallback')->assertJsonPath('data.replayed', false)->assertHeader('Cache-Control', 'no-store, private');
        $this->assertSame('Hi', ChatbotQuestion::firstOrFail()->question);
        $replay = $this->send($this->payload())->assertOk()->assertJsonPath('data.replayed', true);
        $this->assertFalse($replay->headers->has('Set-Cookie'));
        $this->assertArrayNotHasKey('session_hash', $replay->json('data'));
        $this->assertSame(1, ChatbotQuestion::count());
    }

    public function test_origin_and_content_type_reject_without_collection(): void
    {
        foreach (['', 'null', 'http://localhost:9001', 'https://attacker.example'] as $origin) {
            $this->send($this->payload(), ['Origin' => $origin])->assertForbidden();
        }
        $this->post('/api/chatbot/questions', $this->payload(), ['Origin' => 'http://localhost:9000'])->assertStatus(415);
        $this->assertSame(0, ChatbotQuestion::count());
    }

    public function test_schema_validation_and_unicode_bounds_do_not_echo_input(): void
    {
        foreach ([['question' => null], ['question' => ['SECRET']], ['question' => "\u{0085}\u{FEFF}"], ['question' => str_repeat('😀', 1001)], ['session_id' => 'bad-secret'], ['request_id' => strtoupper($this->payload()['request_id']).'F'], ['extra' => 'secret']] as $bad) {
            $response = $this->send(array_replace($this->payload(), $bad))->assertUnprocessable();
            $this->assertStringNotContainsString('SECRET', $response->getContent());
            $this->assertStringNotContainsString('bad-secret', $response->getContent());
        }
        $this->assertSame(0, ChatbotQuestion::count());
        $this->send(array_replace($this->payload(), ['question' => "\u{0085}".str_repeat('😀', 1000)."\u{FEFF}"]))->assertCreated();
    }

    public function test_body_cap_checks_actual_bytes_not_only_declared_length(): void
    {
        $headers = ['CONTENT_TYPE' => 'application/json', 'HTTP_ORIGIN' => 'http://localhost:9000', 'HTTP_ACCEPT' => 'application/json'];
        $this->call('POST', '/api/chatbot/questions', [], [], [], $headers, str_repeat(' ', 16384))->assertUnprocessable();
        $this->call('POST', '/api/chatbot/questions', [], [], [], $headers + ['CONTENT_LENGTH' => '10'], str_repeat(' ', 16385))->assertStatus(413);
        $this->call('POST', '/api/chatbot/questions', [], [], [], $headers, str_repeat(' ', 16385))->assertStatus(413);
        $this->assertSame(0, ChatbotQuestion::count());
    }

    public function test_public_history_and_management_routes_are_absent(): void
    {
        $this->getJson('/api/chatbot/questions')->assertStatus(405);
        $this->getJson('/api/chatbot/faqs')->assertNotFound();
        $this->postJson('/api/chatbot/faqs', [])->assertNotFound();
    }

    public function test_database_failure_response_and_logging_are_sanitized(): void
    {
        $service = \Mockery::mock(QuestionAnswerService::class);
        $service->shouldReceive('submit')->andThrow(new QueryException('mysql', 'INSERT SECRET question', ['SECRET'], new \RuntimeException('SECRET internals')));
        app()->instance(QuestionAnswerService::class, $service);
        Log::shouldReceive('warning')->once()->with('chatbot_request_failed', ['status' => 503]);
        $response = $this->send($this->payload())->assertStatus(503)->assertHeader('Cache-Control', 'no-store, private');
        $this->assertStringNotContainsString('SECRET', $response->getContent());
        $this->assertSame(0, ChatbotQuestion::count());
    }

    public function test_reused_id_conflict_returns_generic_409(): void
    {
        $this->send($this->payload())->assertCreated();
        $response = $this->send(array_replace($this->payload(), ['question' => 'Changed secret']))->assertStatus(409);
        $this->assertStringNotContainsString('Changed secret', $response->getContent());
        $this->assertSame(1, ChatbotQuestion::count());
    }
}
