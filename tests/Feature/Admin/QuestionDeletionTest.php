<?php

namespace Tests\Feature\Admin;

use App\Chatbot\Admin\RestorationSink;
use App\Models\ChatbotQuestion;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\Support\AdminTestCase;

class QuestionDeletionTest extends AdminTestCase
{
    public function test_confirmation_and_restoration_failure_precede_hard_delete(): void
    {
        $user = User::create(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'a-safe-password']);
        $user->is_chatbot_admin = true;
        $user->save();
        $this->actingAs($user)->withSession(['admin_started' => now()->timestamp, 'admin_activity' => now()->timestamp]);
        $q = ChatbotQuestion::create(['question' => 'Potentially private', 'status' => 'unanswered', 'session_hash' => str_repeat('a', 64), 'request_id' => (string) Str::uuid()]);
        $url = '/admin/chatbot/data/questions/'.$q->id;
        $this->deleteJson($url, ['confirmed' => true])->assertStatus(428);
        $this->withSession(['admin_password_confirmed' => now()->timestamp - 900])->deleteJson($url, ['confirmed' => true])->assertStatus(428);
        $this->withSession(['admin_password_confirmed' => now()->timestamp])->deleteJson($url, ['confirmed' => false])->assertStatus(422);
        $sink = new class implements RestorationSink
        {
            public bool $fail = true;

            public array $records = [];

            public function record(array $record): void
            {
                if ($this->fail) {
                    throw new \RuntimeException('private sink failure');
                }
                if (! ChatbotQuestion::find($record['question_id'])) {
                    throw new \RuntimeException('Deletion preceded instruction');
                }
                $this->records[] = $record;
            }
        };
        $this->app->instance(RestorationSink::class, $sink);
        $this->deleteJson($url, ['confirmed' => true])->assertStatus(503)->assertDontSee('private sink failure');
        $this->assertNotNull($q->fresh());
        $sink->fail = false;
        $this->deleteJson($url, ['confirmed' => true])->assertNoContent();
        $this->assertNull($q->fresh());
        $this->assertSame(['question_id', 'created_at', 'deleted_at'], array_keys($sink->records[0]));
        $this->assertStringNotContainsString('Potentially private', json_encode($sink->records));
        $this->deleteJson($url, ['confirmed' => true])->assertNotFound();
    }
}
