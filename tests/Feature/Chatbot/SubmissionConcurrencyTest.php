<?php

namespace Tests\Feature\Chatbot;

use App\Chatbot\QuestionAnswerService;
use App\Models\ChatbotQuestion;
use Illuminate\Support\Facades\DB;
use Tests\Support\ChatbotTestCase;

class SubmissionConcurrencyTest extends ChatbotTestCase
{
    protected bool $transactional = false;

    public function test_parallel_same_tuple_creates_exactly_one_committed_row(): void
    {
        $session = '11111111-1111-4111-8111-111111111111';
        $request = '22222222-2222-4222-8222-222222222222';
        DB::disconnect();
        $children = [];
        $files = [];
        for ($i = 0; $i < 2; $i++) {
            $file = tempnam(sys_get_temp_dir(), 'chatbot-race-');
            $files[] = $file;
            [$parent, $child] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
            $pid = pcntl_fork();
            if ($pid === 0) {
                fclose($parent);
                fread($child, 1);
                DB::purge('mysql');
                try {
                    $result = app(QuestionAnswerService::class)->submit('Race question', $session, $request);
                    file_put_contents($file, json_encode($result));
                    exit(0);
                } catch (\Throwable) {
                    file_put_contents($file, '{}');
                    exit(1);
                }
            }
            fclose($child);
            $children[] = [$pid, $parent];
        }
        foreach ($children as [$pid, $pipe]) {
            fwrite($pipe, 'x');
            fclose($pipe);
        }
        foreach ($children as [$pid]) {
            pcntl_waitpid($pid, $status);
            $this->assertSame(0, pcntl_wexitstatus($status));
        }
        $results = array_map(fn ($file) => json_decode(file_get_contents($file), true), $files);
        foreach ($files as $file) {
            unlink($file);
        }
        $this->assertEqualsCanonicalizing([false, true], array_column($results, 'replayed'));
        $this->assertSame(1, ChatbotQuestion::where('request_id', $request)->count());
        ChatbotQuestion::where('request_id', $request)->delete();
    }
}
