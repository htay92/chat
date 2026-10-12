<?php

namespace Tests\Unit\Chatbot;

use App\Chatbot\Admin\FileRestorationSink;
use Tests\TestCase;

class RestorationSinkTest extends TestCase
{
    public function test_private_files_permissions_and_thirty_day_expiry(): void
    {
        $directory = sys_get_temp_dir().'/chatbot-sink-'.bin2hex(random_bytes(8));
        config(['chatbot_admin.restoration_directory' => $directory]);
        $sink = new FileRestorationSink;
        $record = ['question_id' => 123, 'created_at' => now('UTC')->toISOString(), 'deleted_at' => now('UTC')->toISOString()];
        try {
            $sink->record($record);
            $files = glob($directory.'/*.json');
            $this->assertCount(1, $files);
            $this->assertSame($record, json_decode(file_get_contents($files[0]), true));
            $this->assertSame(0700, fileperms($directory) & 0777);
            $this->assertSame(0600, fileperms($files[0]) & 0777);
            $this->assertSame(0, $sink->expire());
            touch($files[0], now()->timestamp - 30 * 86400);
            clearstatcache();
            $this->assertSame(1, $sink->expire());
            $this->assertSame(0, $sink->expire());
        } finally {
            foreach (glob($directory.'/*.json') ?: [] as $file) {
                unlink($file);
            }
            if (is_dir($directory)) {
                rmdir($directory);
            }
        }
    }

    public function test_expiry_rejects_a_public_directory_before_deleting(): void
    {
        $directory = sys_get_temp_dir().'/chatbot-public-sink-'.bin2hex(random_bytes(8));
        mkdir($directory, 0700);
        $this->app->usePublicPath($directory);
        config(['chatbot_admin.restoration_directory' => $directory]);
        $file = $directory.'/20000101000000-'.str_repeat('a', 32).'.json';
        file_put_contents($file, '{}');
        touch($file, now()->timestamp - 31 * 86400);
        try {
            $rejected = false;
            try {
                (new FileRestorationSink)->expire();
            } catch (\RuntimeException) {
                $rejected = true;
            }
            $this->assertTrue($rejected, 'Public restoration directory must fail closed');
            $this->assertFileExists($file);
        } finally {
            if (file_exists($file)) {
                unlink($file);
            }
            rmdir($directory);
        }
    }
}
