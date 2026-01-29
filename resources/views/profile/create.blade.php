@extends('layout.master')

@section('content')
    <div class="min-h-screen px-6 py-6 ">

        {{-- PAGE HEADER --}}
        <div class="p-6 mb-6 bg-white shadow-lg rounded-2xl">
            <h1 class="text-2xl font-bold text-gray-800">Tambah User</h1>
            <p class="text-sm text-gray-500">
                Buat akun pengguna baru untuk sistem
            </p>
        </div>

        {{-- FORM WRAPPER --}}
        <div class="bg-white shadow-md rounded-2xl">

            {{-- FORM HEADER --}}
            <div class="px-6 py-4 border-b">
                <h2 class="text-lg font-semibold text-gray-700">User Information</h2>
            </div>

            {{-- FORM BODY --}}
            <form action="{{ route('users.store') }}" method="POST" class="p-6 space-y-6">
                @csrf

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

                    {{-- NAME --}}
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">
                            Nama Lengkap
                        </label>
                        <input type="text" name="name" value="{{ old('name') }}"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-600 focus:outline-none
                        @error('name') border-red-500 @enderror">

                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- USERNAME --}}
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">
                            Username
                        </label>
                        <input type="text" name="username" value="{{ old('username') }}"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-600 focus:outline-none
                        @error('username') border-red-500 @enderror">

                        @error('username')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- EMAIL --}}
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">
                            Email
                        </label>
                        <input type="email" name="email" value="{{ old('email') }}"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-600 focus:outline-none
                        @error('email') border-red-500 @enderror">

                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- ROLE --}}
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">
                            Role
                        </label>
                        <select name="role"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-600 focus:outline-none
                        @error('role') border-red-500 @enderror">
                            <option value="">-- Pilih Role --</option>
                            <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                            <option value="user" {{ old('role') == 'user' ? 'selected' : '' }}>User</option>
                        </select>

                        @error('role')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- PASSWORD --}}
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">
                            Password
                        </label>
                        <input type="password" name="password"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-600 focus:outline-none
                        @error('password') border-red-500 @enderror">

                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- CONFIRM PASSWORD --}}
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">
                            Konfirmasi Password
                        </label>
                        <input type="password" name="password_confirmation"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-600 focus:outline-none">
                    </div>
                </div>

                {{-- ACTION BUTTON --}}
                <div class="flex justify-end gap-3 pt-6 border-t">
                    <a href="{{ route('users.index') }}"
                        class="px-5 py-2 text-sm font-medium text-gray-700 transition bg-gray-200 rounded-lg hover:bg-gray-300">
                        Batal
                    </a>

                    <button type="submit"
                        class="px-6 py-2 text-sm font-semibold text-white transition bg-blue-600 rounded-lg hover:bg-blue-700">
                        Simpan User
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
