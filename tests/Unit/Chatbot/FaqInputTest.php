<?php

namespace Tests\Unit\Chatbot;

use App\Chatbot\FaqInput;
use PHPUnit\Framework\TestCase;

class FaqInputTest extends TestCase
{
    public function test_protected_stdin_accepts_multiline_json_and_rejects_extra_or_oversized_fields(): void
    {
        $input = new FaqInput;
        foreach ([json_encode(['question' => "Hi\nworld", 'answer' => "Line one\nLine two"]) => true, '{"question":"Hi","answer":"Ok","operator":"forged"}' => false, str_repeat('x', 32769) => false] as $text => $valid) {
            $stream = fopen('php://temp', 'r+');
            fwrite($stream, $text);
            rewind($stream);
            try {
                $result = $input->read($stream);
                $this->assertTrue($valid);
                $this->assertSame(["Hi\nworld", "Line one\nLine two"], $result);
            } catch (\InvalidArgumentException|\JsonException) {
                $this->assertFalse($valid);
            } finally {
                fclose($stream);
            }
        }
    }
}
