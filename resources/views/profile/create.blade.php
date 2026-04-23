@extends('layout.master')

@section('content')
    <div class="min-h-screen px-6 py-6" x-data="{ role: '{{ old('role') }}' }">

        {{-- PAGE HEADER --}}
        <div class="p-6 mb-6 bg-white shadow-lg rounded-2xl">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-blue-100 rounded-lg">
                    <i class="text-xl text-blue-600 fa-solid fa-user-plus"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Tambah User Baru</h1>
                    <p class="text-sm text-gray-500">Daftarkan akun pengguna baru ke dalam sistem Nuctech Warehouse.</p>
                </div>
            </div>
        </div>

        {{-- FORM WRAPPER --}}
        <div class="max-w-4xl bg-white shadow-md rounded-2xl">

            <div class="px-6 py-4 border-b">
                <h2 class="text-lg font-semibold text-gray-700">Informasi Akun</h2>
            </div>

            <form action="{{ route('users.store') }}" method="POST" class="p-6 space-y-6">
                @csrf

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

                    {{-- NAME --}}
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">Nama Lengkap</label>
                        <input type="text" name="name" value="{{ old('name') }}" placeholder="Contoh: Budi Santoso"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-600 focus:outline-none @error('name') border-red-500 @enderror">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- USERNAME --}}
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">Username</label>
                        <input type="text" name="username" value="{{ old('username') }}" placeholder="username123"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-600 focus:outline-none @error('username') border-red-500 @enderror">
                        @error('username')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- EMAIL --}}
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="user@nuctech.com"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-600 focus:outline-none @error('email') border-red-500 @enderror">
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- ROLE --}}
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">Role Pengguna</label>
                        <select name="role" x-model="role"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-600 focus:outline-none @error('role') border-red-500 @enderror">
                            <option value="">-- Pilih Role --</option>
                            <option value="superadmin">Superadmin (Kantor Pusat)</option>
                            <option value="admin_site">Admin Site (Cabang/Lokasi)</option>
                        </select>
                        @error('role')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- SITE (Hanya muncul jika role = admin_site) --}}
                    <div x-show="role === 'admin_site'" x-transition>
                        <label class="block mb-1 text-sm font-medium text-gray-700">Penugasan Site</label>
                        <select name="site_id"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-600 focus:outline-none @error('site_id') border-red-500 @enderror">
                            <option value="">-- Pilih Lokasi Site --</option>
                            @foreach ($sites as $site)
                                <option value="{{ $site->id }}" {{ old('site_id') == $site->id ? 'selected' : '' }}>
                                    {{ $site->machine_name }} ({{ $site->branch->name ?? 'N/A' }})
                                </option>
                            @endforeach
                        </select>
                        @error('site_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="hidden md:block" x-show="role !== 'admin_site'">
                        {{-- Spacer --}}
                    </div>

                    {{-- PASSWORD --}}
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">Password</label>
                        <input type="password" name="password" placeholder="Minimal 6 karakter"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-600 focus:outline-none @error('password') border-red-500 @enderror">
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- CONFIRM PASSWORD --}}
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">Konfirmasi Password</label>
                        <input type="password" name="password_confirmation" placeholder="Ulangi password"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-600 focus:outline-none">
                    </div>
                </div>

                {{-- ACTION BUTTON --}}
                <div class="flex justify-end gap-3 pt-6 border-t">
                    <a href="{{ route('users.index') }}"
                        class="px-5 py-2 text-sm font-medium text-gray-700 transition bg-gray-100 rounded-lg hover:bg-gray-200">
                        Batal
                    </a>

                    <button type="submit"
                        class="px-6 py-2 text-sm font-bold text-white transition bg-blue-600 rounded-lg shadow-md hover:bg-blue-700 active:scale-95">
                        <i class="mr-1 fa-solid fa-save"></i> Simpan User
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
