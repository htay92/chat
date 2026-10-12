<?php

namespace App\Chatbot\Admin;

class FileRestorationSink implements RestorationSink
{
    public function record(array $record): void
    {
        $directory = config('chatbot_admin.restoration_directory');
        if (! is_string($directory) || ! str_starts_with($directory, '/') || is_link($directory)) {
            throw new \RuntimeException;
        }
        if (! is_dir($directory) && ! @mkdir($directory, 0700, true)) {
            throw new \RuntimeException;
        }
        $real = realpath($directory);
        if ($real === false || str_starts_with($real.'/', realpath(public_path()).'/') || ! @chmod($real, 0700)) {
            throw new \RuntimeException;
        }
        $path = $real.'/'.now('UTC')->format('YmdHis').'-'.bin2hex(random_bytes(16)).'.json';
        $stream = @fopen($path, 'x');
        if ($stream === false) {
            throw new \RuntimeException;
        }
        try {
            if (! chmod($path, 0600)) {
                throw new \RuntimeException;
            }
            $json = json_encode($record, JSON_THROW_ON_ERROR)."\n";
            if (fwrite($stream, $json) !== strlen($json) || ! fflush($stream) || ! fsync($stream)) {
                throw new \RuntimeException;
            }
        } finally {
            fclose($stream);
        }
    }

    public function expire(): int
    {
        $directory = config('chatbot_admin.restoration_directory');
        if (! is_string($directory) || ! is_dir($directory)) {
            return 0;
        }
        $real = realpath($directory);
        if (! str_starts_with($directory, '/') || is_link($directory) || $real === false
            || str_starts_with($real.'/', realpath(public_path()).'/')) {
            throw new \RuntimeException;
        }
        $count = 0;
        foreach (new \DirectoryIterator($directory) as $file) {
            if ($count >= 500) {
                break;
            }
            if ($file->isFile() && ! $file->isLink() && preg_match('/\A\d{14}-[a-f0-9]{32}\.json\z/', $file->getFilename()) && $file->getMTime() <= now()->timestamp - 30 * 86400) {
                if (! unlink($file->getPathname())) {
                    throw new \RuntimeException;
                }
                $count++;
            }
        }

        return $count;
    }
}
