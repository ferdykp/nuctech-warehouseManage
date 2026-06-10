<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$roles  <-- Menggunakan variadic parameter untuk menerima banyak role
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // 1. Pastikan pengguna sudah login
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // 2. Ambil role user saat ini (diubah ke lowercase agar menghindari typo kapital)
        $userRole = strtolower(Auth::user()->role);

        // Jika rute dikirim menggunakan pipa 'superadmin|manager', kita pecah dulu menjadi array.
        // Jika dikirim menggunakan koma 'superadmin,manager', splat otomatis menjadikannya array.
        $allowedRoles = [];
        foreach ($roles as $role) {
            $allowedRoles = array_merge($allowedRoles, explode('|', strtolower($role)));
        }

        // 3. Cek apakah role user ada di dalam daftar role yang diizinkan
        if (!in_array($userRole, $allowedRoles)) {
            abort(403, 'Anda tidak memiliki hak akses untuk halaman ini.');
        }

        return $next($request);
    }
}
