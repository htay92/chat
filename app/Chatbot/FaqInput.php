<?php

namespace App\Chatbot;

use Illuminate\Console\Command;
use InvalidArgumentException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class FaqInput
{
    /** @param resource $stream */
    public function read($stream): array
    {
        $raw = stream_get_contents($stream, 32769);
        if ($raw === false || strlen($raw) > 32768) {
            throw new InvalidArgumentException;
        }
        $data = json_decode($raw, true, 8, JSON_THROW_ON_ERROR);
        if (! is_array($data) || count($data) !== 2 || ! is_string($data['question'] ?? null) || ! is_string($data['answer'] ?? null)) {
            throw new InvalidArgumentException;
        }

        return [$data['question'], $data['answer']];
    }

    public function confirm(Command $command, string $prompt): bool
    {
        if (! $command->option('stdin')) {
            return $command->confirm($prompt);
        }
        // Piped content cannot supply publication consent. Use the controlling terminal.
        $terminal = @fopen('/dev/tty', 'r+');
        if ($terminal === false) {
            throw new \RuntimeException('Interactive operator confirmation is required.');
        }
        try {
            $input = new ArrayInput([]);
            $input->setStream($terminal);

            return (new QuestionHelper)->ask($input, $command->getOutput(), new ConfirmationQuestion($prompt.' [y/N] ', false));
        } finally {
            fclose($terminal);
        }
    }
}
