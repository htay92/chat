<?php

namespace Tests\Feature\Admin;

use App\Chatbot\FaqService;
use App\Models\Faq;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tests\Support\AdminTestCase;

class FaqConcurrencyTest extends AdminTestCase
{
    public function test_separate_mysql_connections_cannot_overwrite_same_revision(): void
    {
        $user = User::create(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'a-safe-password']);
        $user->is_chatbot_admin = true;
        $user->save();
        $this->actingAs($user)->withSession(['admin_started' => now()->timestamp, 'admin_activity' => now()->timestamp]);
        $service = app(FaqService::class);
        $faq = $service->create('Question', 'Initial');
        $revision = $service->revision($faq);
        DB::commit();
        DB::disconnect();
        $files = [];
        $pids = [];
        $start = microtime(true) + 0.2;
        foreach (['First', 'Second'] as $answer) {
            $file = tempnam(sys_get_temp_dir(), 'faq-race-');
            $files[] = $file;
            $pid = pcntl_fork();
            if ($pid === 0) {
                DB::purge('mysql');
                Redis::purge('chatbot');
                while (microtime(true) < $start) {
                    usleep(1000);
                }
                $response = $this->patchJson('/admin/chatbot/data/faqs/'.$faq->id, ['question' => 'Question', 'answer' => $answer, 'revision' => $revision]);
                file_put_contents($file, (string) $response->getStatusCode());
                exit(0);
            }
            $pids[] = $pid;
        }
        foreach ($pids as $pid) {
            pcntl_waitpid($pid, $status);
            $this->assertSame(0, pcntl_wexitstatus($status));
        }
        $statuses = array_map(fn ($file) => (int) file_get_contents($file), $files);
        sort($statuses);
        foreach ($files as $file) {
            unlink($file);
        }
        $this->assertSame([200, 409], $statuses);
        $winner = Faq::findOrFail($faq->id);
        // A console service edit uses the same revision contract as web mutations.
        $winner = $service->update($winner->id, $service->revision($winner), 'Question', 'Console winner');
        foreach ([true, false] as $target) {
            $this->postJson('/admin/chatbot/data/faqs/'.$faq->id.'/activation', ['revision' => $revision, 'is_active' => $target, 'confirmed' => true])->assertStatus(409);
        }
        $this->withSession(['admin_password_confirmed' => now()->timestamp])->deleteJson('/admin/chatbot/data/faqs/'.$faq->id, ['revision' => $revision, 'confirmed' => true])->assertStatus(409);
        $this->assertSame('Console winner', $winner->fresh()->answer);
        Faq::whereKey($faq->id)->delete();
        User::whereKey($user->id)->delete();
        DB::beginTransaction();
    }
}
