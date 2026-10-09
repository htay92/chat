<?php

namespace App\Chatbot;

use App\Models\Faq;
use Illuminate\Console\Command;

class FaqConsole
{
    public function run(Command $command, string $action): int
    {
        $access = app(OperatorAccess::class);
        $id = null;
        try {
            $access->identity();
            $access->audit($action, null, 'started');
            $service = app(FaqService::class);
            if ($action === 'list') {
                foreach (Faq::orderBy('id')->limit(50)->get() as $faq) {
                    $command->line($faq->id.' '.($faq->is_active ? 'active' : 'inactive').' '.OperatorAccess::escape($faq->question));
                }
            } elseif ($action === 'create') {
                [$q, $a] = $this->content($command);
                app(FaqNormalizer::class)->question($q);
                app(FaqNormalizer::class)->answer($a);
                $this->preview($command, $q, $a);
                if (! app(FaqInput::class)->confirm($command, 'Save inactive FAQ?')) {
                    $access->audit($action, null, 'cancelled');

                    return 0;
                }
                $id = $service->create($q, $a)->id;
                $command->info('Created inactive FAQ '.$id.'.');
            } else {
                $id = filter_var($command->argument('faq'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: throw new \InvalidArgumentException;
                $faq = Faq::findOrFail($id);
                $revision = $service->revision($faq);
                $this->preview($command, $faq->question, $faq->answer);
                if ($action === 'update') {
                    [$q, $a] = $this->content($command);
                    app(FaqNormalizer::class)->question($q);
                    app(FaqNormalizer::class)->answer($a);
                    $this->preview($command, $q, $a);
                    if (! app(FaqInput::class)->confirm($command, 'Save changes (content edits become inactive)?')) {
                        $access->audit($action, $id, 'cancelled');

                        return 0;
                    }
                    $service->update($id, $revision, $q, $a);
                } elseif ($action !== 'show') {
                    $prompt = $action === 'activate' ? 'Activate FAQ?' : 'Deactivate FAQ?';
                    if (! $command->confirm($prompt)) {
                        $access->audit($action, $id, 'cancelled');

                        return 0;
                    }
                    $service->activate($id, $revision, $action === 'activate');
                }
            }
            $access->audit($action, $id, 'completed');

            return 0;
        } catch (\Throwable) {
            try {
                $access->audit($action, $id, 'failed');
            } catch (\Throwable) {
            }
            $command->error('FAQ operation failed. Check access, input, duplicate keys or reload a changed record.');

            return 1;
        }
    }

    private function content(Command $command): array
    {
        if ($command->option('stdin')) {
            return app(FaqInput::class)->read(STDIN);
        }

        return [$command->ask('Question (one line; for multiline use protected JSON stdin with --stdin)'), $command->ask('Prepared answer')];
    }

    private function preview(Command $command, string $q, string $a): void
    {
        $command->line('Question: '.OperatorAccess::escape($q));
        $command->line('Answer: '.OperatorAccess::escape($a));
        $command->line('Match key: '.app(FaqNormalizer::class)->digest(app(FaqNormalizer::class)->normalize($q)));
    }
}
