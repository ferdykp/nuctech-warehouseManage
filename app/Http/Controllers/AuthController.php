<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login()
    {
        // Kalau sudah login, jangan boleh balik ke login
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function loginAuth(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:255',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput($request->only('username'));
        }

        if (Auth::attempt($request->only('username', 'password'))) {

            $request->session()->regenerate();
            $request->session()->put('last_activity_at', now()->timestamp);

            return redirect()->route('dashboard')
                ->with('success', 'Login successful');
        }

        return back()
            ->withErrors(['username' => 'Username atau password salah'])
            ->withInput($request->only('username'));
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();     // 🔥 WAJIB
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'Logout successful');
    }
}
