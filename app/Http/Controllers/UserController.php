<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{


    public function index()
    {
        // Hanya superadmin yang boleh melihat daftar semua user
        if (auth()->user()->role !== 'superadmin') {
            abort(403, 'Akses ditolak.');
        }

        $users = User::with('site')->get();
        return view('profile.profileList', compact('users'));
    }

    public function create()
    {
        if (auth()->user()->role !== 'superadmin') {
            abort(403);
        }

        $sites = Site::all();
        return view('profile.create', compact('sites'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|min:6|confirmed',
            'role'     => 'required|in:superadmin,admin_site',
            'site_id'  => 'required_if:role,admin_site|nullable|exists:sites,id',
        ]);

        User::create([
            'name'     => $request->name,
            'username' => $request->username,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $request->role,
            'site_id'  => $request->role === 'superadmin' ? null : $request->site_id,
        ]);

        return redirect()->route('users.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function show($id)
    {
        $user = User::with('site')->findOrFail($id);

        // Proteksi: Admin site tidak boleh melihat profil orang lain kecuali dirinya sendiri
        if (auth()->user()->role !== 'superadmin' && auth()->id() !== $user->id) {
            abort(403);
        }

        return view('profile.profile', compact('user'));
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);

        if (auth()->user()->role !== 'superadmin' && auth()->id() !== $user->id) {
            abort(403);
        }

        $sites = Site::all();
        return view('profile.profileEdit', compact('user', 'sites'));
    }

    public function update(Request $request, $id) // Gunakan ID agar lebih stabil
    {
        $user = User::findOrFail($id);

        $validatedData = $request->validate([
            'name'     => 'required|string|max:255',
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'email'    => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role'     => 'required|in:superadmin,admin_site',
            'site_id'  => 'required_if:role,admin_site|nullable|exists:sites,id',
            'password' => 'nullable|min:6|confirmed',
        ]);

        $user->name = $validatedData['name'];
        $user->username = $validatedData['username'];
        $user->email = $validatedData['email'];

        // Hanya superadmin yang bisa ubah role & site orang lain
        if (auth()->user()->role === 'superadmin') {
            $user->role = $validatedData['role'];
            $user->site_id = ($validatedData['role'] === 'superadmin') ? null : $validatedData['site_id'];
        }

        if (!empty($validatedData['password'])) {
            $user->password = Hash::make($validatedData['password']);
        }

        $user->save();

        return redirect()->route('users.index')->with('success', 'User berhasil diperbarui');
    }

    public function destroy($id)
    {
        if (auth()->user()->role !== 'superadmin') {
            abort(403);
        }

        $user = User::findOrFail($id);

        // Jangan biarkan superadmin menghapus dirinya sendiri
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak bisa menghapus akun sendiri.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'User berhasil dihapus.');
    }
}
