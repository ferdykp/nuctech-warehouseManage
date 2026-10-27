<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SensitiveFiles
{
    public const DIRECTORIES = ['receipts', 'signatures', 'leave_attachments'];

    public static function normalize(string $path): string
    {
        $path = preg_replace('#^(?:storage/|public/)+#', '', $path);
        if (! preg_match('#^(receipts|signatures|leave_attachments)/[^\\\\\x00]+$#D', $path)
            || in_array('..', explode('/', $path), true) || in_array('.', explode('/', $path), true)) {
            throw new \InvalidArgumentException('Invalid sensitive attachment path.');
        }

        return $path;
    }

    public static function path(string $path): string
    {
        return Storage::disk('sensitive')->path(self::normalize($path));
    }

    public static function receiptPath(string $path): ?string
    {
        $path = self::normalize($path);
        if (! str_starts_with($path, 'receipts/')) {
            throw new \InvalidArgumentException('Invalid receipt attachment path.');
        }

        foreach (['sensitive', 'public'] as $diskName) {
            $disk = Storage::disk($diskName);
            if ($disk->exists($path)) {
                $file = $disk->path($path);
                if (is_file($file) && is_readable($file)) {
                    return $file;
                }
            }
        }

        return null;
    }

    public static function receiptResponse(string $path)
    {
        $file = self::receiptPath($path);
        abort_unless($file, 404);

        return response()->file($file, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "sandbox; default-src 'none'",
        ]);
    }

    public static function temporaryPath(string $prefix): string
    {
        Storage::disk('sensitive')->makeDirectory('temporary');

        return Storage::disk('sensitive')->path('temporary/'.$prefix.'_'.Str::uuid().'.pdf');
    }

    public static function response(string $path)
    {
        $path = self::normalize($path);
        abort_unless(Storage::disk('sensitive')->exists($path), 404);

        return response()->file(self::path($path), [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "sandbox; default-src 'none'",
        ]);
    }
}
