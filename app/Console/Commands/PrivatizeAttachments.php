<?php

namespace App\Console\Commands;

use App\Services\SensitiveFiles;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PrivatizeAttachments extends Command
{
    protected $signature = 'security:privatize-attachments {--dry-run : List files without moving them}';

    protected $description = 'Move legacy sensitive public files to private storage, verifying content before deletion';

    public function handle(): int
    {
        $public = Storage::disk('public');
        $private = Storage::disk('sensitive');
        $count = 0;
        foreach (SensitiveFiles::DIRECTORIES as $directory) {
            foreach ($public->allFiles($directory) as $path) {
                $path = SensitiveFiles::normalize($path);
                if ($this->option('dry-run')) {
                    $count++;

                    continue;
                }
                // Never overwrite a different private document, even on a retry.
                if (! $private->exists($path)) {
                    $stream = $public->readStream($path);
                    if (! is_resource($stream)) {
                        $this->error('Cannot read '.$path);

                        return self::FAILURE;
                    }
                    try {
                        $private->put($path, $stream);
                    } finally {
                        fclose($stream);
                    }
                }
                if (! hash_equals(hash_file('sha256', $public->path($path)), hash_file('sha256', $private->path($path)))) {
                    $this->error('Content differs; public copy retained: '.$path);

                    return self::FAILURE;
                }
                if (! $public->delete($path)) {
                    $this->error('Cannot remove public copy: '.$path);

                    return self::FAILURE;
                }
                $count++;
            }
        }
        $this->info($count.($this->option('dry-run') ? ' file(s) would be moved.' : ' file(s) moved and verified.'));

        return self::SUCCESS;
    }
}
