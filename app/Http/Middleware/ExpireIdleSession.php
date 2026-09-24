<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExpireIdleSession
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $lastActivity = (int) $request->session()->get('last_activity_at', now()->timestamp);
            $timeout = max(1, (int) config('session.idle_timeout', 30)) * 60;

            if (now()->timestamp - $lastActivity >= $timeout) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Sesi berakhir. Silakan login kembali.'], 401);
                }

                return redirect()->route('login')->with('warning', 'Sesi berakhir karena tidak ada aktivitas. Silakan login kembali.');
            }

            // A background status check must never extend the session.
            if (!$request->routeIs('session.status')) {
                $request->session()->put('last_activity_at', now()->timestamp);
            }
        }

        return $next($request);
    }
}
