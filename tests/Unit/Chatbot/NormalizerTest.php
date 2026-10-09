<?php

namespace Tests\Unit\Chatbot;

use App\Chatbot\FaqNormalizer;
use PHPUnit\Framework\TestCase;

class NormalizerTest extends TestCase
{
    public function test_unicode_policy_and_exact_matching(): void
    {
        $n = new FaqNormalizer;
        $this->assertSame('café hello', $n->normalize("\u{FEFF}CAFE\u{0301}\u{0085}\nHELLO\u{00A0}"));
        $this->assertSame("a\nb", $n->question("\u{FEFF} a\nb\u{0085}"));
        $this->assertSame('straße', $n->normalize('Straße'));
        $this->assertNotSame($n->normalize('Straße'), $n->normalize('STRASSE'));
        $this->assertNotSame($n->normalize('café?'), $n->normalize('cafe'));
        $this->assertSame(str_repeat('😀', 1000), $n->question(str_repeat('😀', 1000)));
        foreach (['', "\u{0085}\u{FEFF}", str_repeat('😀', 1001)] as $invalid) {
            try {
                $n->question($invalid);
                $this->fail('Invalid question accepted');
            } catch (\InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_shared_frontend_backend_trimming_fixtures(): void
    {
        $normalizer = new FaqNormalizer;
        $fixtures = json_decode(file_get_contents(__DIR__.'/../../fixtures/chatbot-unicode.json'), true, 8, JSON_THROW_ON_ERROR);
        foreach ($fixtures as $fixture) {
            $this->assertSame($fixture['text'], $normalizer->trim($fixture['input']));
            if ($fixture['valid']) {
                $this->assertSame($fixture['text'], $normalizer->question($fixture['input']));
            } else {
                try {
                    $normalizer->question($fixture['input']);
                    $this->fail('Invalid input accepted');
                } catch (\InvalidArgumentException) {
                    $this->addToAssertionCount(1);
                }
            }
        }
    }
}
