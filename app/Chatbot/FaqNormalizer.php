<?php

namespace App\Chatbot;

use InvalidArgumentException;
use Normalizer;

class FaqNormalizer
{
    private const SPACE = '[\p{Z}\x{0009}-\x{000D}\x{0085}\x{FEFF}]';

    public function trim(string $text): string
    {
        return preg_replace('/\A'.self::SPACE.'+|'.self::SPACE.'+\z/u', '', $text) ?? throw new InvalidArgumentException('Invalid Unicode.');
    }

    public function question(string $text): string
    {
        return $this->bounded($text, 1000);
    }

    public function answer(string $text): string
    {
        return $this->bounded($text, 4000);
    }

    private function bounded(string $text, int $max): string
    {
        if (! mb_check_encoding($text, 'UTF-8')) {
            throw new InvalidArgumentException('Invalid Unicode.');
        }
        $text = $this->trim($text);
        if ($text === '' || mb_strlen($text, 'UTF-8') > $max) {
            throw new InvalidArgumentException('Text is blank or exceeds the permitted length.');
        }

        return $text;
    }

    public function normalize(string $text): string
    {
        $text = Normalizer::normalize($text, Normalizer::FORM_C);
        if ($text === false) {
            throw new InvalidArgumentException('Invalid Unicode.');
        }

        return mb_strtolower(preg_replace('/'.self::SPACE.'+/u', ' ', $this->trim($text)), 'UTF-8');
    }

    public function digest(string $normalized): string
    {
        return hash('sha256', $normalized);
    }
}
