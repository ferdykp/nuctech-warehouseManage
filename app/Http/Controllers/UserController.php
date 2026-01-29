<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();
        return view('profile.profileList', compact('users'));
    }

    // Menampilkan form tambah user
    public function create()
    {
        return view('profile.create');
    }

    // Memproses data user baru
    public function store(Request $request)
    {
        // Validasi input
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255',
            'email' => 'required|string|max:255',
            'password' => 'required|min:6|confirmed',
            'role' => 'required|in:admin,user',
        ]);

        // Simpan data user baru
        User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password), // Enkripsi password
            'role' => $request->role,
        ]);

        return redirect()->route('users.create')->with('success', 'User berhasil ditambahkan.');
    }

    // Menampilkan detail data user berdasarkan ID
    public function show($id)
    {
        // Cari user berdasarkan ID
        $user = User::findOrFail($id); // Akan melempar error 404 jika user tidak ditemukan

        return view('profile.profile', compact('user'));
    }


    public function edit($id)
    {
        $user = User::findOrFail($id);
        return view('profile.profileEdit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255' . $user->id,
            'email' => 'required|string|max:255' . $user->id,
            'role' => 'required|in:admin,user',
            'password' => 'nullable|min:5|confirmed',
        ]);

        // Update data user
        $user->name = $validatedData['name'];
        $user->username = $validatedData['username'];
        $user->email = $validatedData['email'];
        $user->role = $validatedData['role'];

        // Jika password diisi, baru diupdate
        if (!empty($validatedData['password'])) {
            $user->password = bcrypt($validatedData['password']);
        }

        $user->save();

        return redirect()->route('profile.index')->with('success', 'User berhasil diperbarui');
    }


    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // Log sebelum penghapusan

        $user->delete();

        // Log setelah penghapusan
        return redirect()->route('profile.index')->with('success', 'User berhasil dihapus.');
    }
}
