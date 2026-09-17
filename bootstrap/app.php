<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Services\TelegramService;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role'    => \App\Http\Middleware\RoleMiddleware::class,
            'nocache' => \App\Http\Middleware\NoCache::class,
        ]);
        $middleware->trustProxies(
            at: '*'
        );

        $middleware->redirectGuestsTo(fn() => route('login'));
    })

    ->withExceptions(function (Exceptions $exceptions) {
        // Kirim notifikasi Telegram saat terjadi Unhandled Exception / Error 500
        $exceptions->reportable(function (\Throwable $e) {
            // Abaikan jika error terjadi di environment local (opsional)
            if (app()->environment('local')) {
                return;
            }

            $msg = "⚠️ <b>[SYSTEM ERROR ALERT]</b>\n\n";
            $msg .= "<b>Message:</b> " . e($e->getMessage()) . "\n";
            $msg .= "<b>File:</b> " . e($e->getFile()) . " (Line " . $e->getLine() . ")\n";
            $msg .= "<b>URL:</b> " . e(request()?->fullUrl() ?? 'N/A') . "\n";
            $msg .= "<b>User ID:</b> " . (auth()->id() ?? 'Guest') . "\n";
            $msg .= "<b>Time:</b> " . now()->format('Y-m-d H:i:s') . " WIB";

            TelegramService::sendMessage($msg);
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
