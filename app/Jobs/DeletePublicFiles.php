<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class DeletePublicFiles implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $backoff = 60;

    public function __construct(public array $paths, public string $disk = 'public') {}

    public function handle(): void
    {
        foreach (array_unique(array_filter($this->paths)) as $path) {
            if ($this->disk === 'sensitive') {
                $path = \App\Services\SensitiveFiles::normalize($path);
            }
            // A retry is safe if an earlier attempt already removed the file.
            if (Storage::disk($this->disk)->exists($path) && ! Storage::disk($this->disk)->delete($path)) {
                throw new \RuntimeException('Could not remove archived attachment.');
            }
        }
    }
}
