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
     * @param  string  ...$roles
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // 1. Pastikan pengguna sudah login secara fisik di sistem
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // PERBAIKAN: Deteksi jika session aktif tapi data user di database hilang (akibat migrate:fresh)
        if (is_null(Auth::user())) {
            Auth::logout(); // Hancurkan session login di server

            $request->session()->invalidate(); // Bersihkan data session
            $request->session()->regenerateToken(); // Buat ulang token CSRF demi keamanan

            // Lempar otomatis ke halaman login Anda
            return redirect()->route('login')->with('error', 'Sesi login Anda tidak valid. Silakan masuk kembali.');
        }

        // 2. Ambil role user saat ini (Aman dari error "property on null" karena sudah divalidasi di atas)
        $userRole = strtolower(Auth::user()->role);

        // Jika rute dikirim menggunakan pipa 'superadmin|manager', kita pecah dulu menjadi array.
        $allowedRoles = [];
        foreach ($roles as $role) {
            $allowedRoles = array_merge($allowedRoles, explode('|', strtolower($role)));
        }

        // 3. Cek apakah role user ada di dalam daftar role yang diizinkan
        if (!in_array($userRole, $allowedRoles)) {
            abort(403, 'Anda tidak memiliki hak akses untuk halaman ini.');
        }

        // --- TAMBAHAN UNTUK MENCEGAH TOMBOL BACK BROWSER ---
        $response = $next($request);

        // Menambahkan header anti-cache agar browser meminta data baru ke server saat di-back
        return $response->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sun, 02 Jan 1990 00:00:00 GMT');
    }
}
