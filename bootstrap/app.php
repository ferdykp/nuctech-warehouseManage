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
        $middleware->web(prepend: [\App\Http\Middleware\NoCache::class], append: [\App\Http\Middleware\ExpireIdleSession::class]);
        $middleware->validateCsrfTokens(except: ['telegram/webhook']);
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
        $exceptions->respond(function (\Symfony\Component\HttpFoundation\Response $response) {
            if ($response->getStatusCode() !== 419) {
                return $response;
            }
            if (request()->expectsJson()) {
                return response()->json(['message' => 'Sesi formulir berakhir. Muat ulang halaman dan login kembali.'], 419);
            }
            return redirect()->route('login')->with('warning', 'Sesi formulir berakhir. Silakan login kembali.');
        });
        // Kirim notifikasi Telegram saat terjadi Unhandled Exception / Error 500
        $exceptions->reportable(function (\Throwable $e) {
            // Abaikan jika error terjadi di environment local (opsional)
            if (!app()->environment('production')) {
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
