@extends('layout.master')

@section('content')
    <div class="min-h-screen px-6 py-6 ">

        {{-- PAGE HEADER --}}
        <div class="p-6 mb-6 bg-white shadow-lg rounded-2xl">
            <h1 class="text-2xl font-bold text-gray-800">Edit User</h1>
            <p class="text-sm text-gray-500">Kelola dan perbarui data pengguna</p>
        </div>

        {{-- FORM WRAPPER --}}
        <div class="bg-white shadow-lg rounded-2xl">

            {{-- FORM HEADER --}}
            <div class="px-6 py-4 border-b">
                <h2 class="text-lg font-semibold text-gray-700">User Information</h2>
            </div>

            {{-- FORM BODY --}}
            <form action="{{ route('users.update', $user->id) }}" method="POST" class="p-6 space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

                    {{-- USERNAME --}}
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">
                            Username
                        </label>
                        <input type="text" name="username" value="{{ old('username', $user->username) }}" required
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-600 focus:outline-none">

                        @error('username')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- EMAIL --}}
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">
                            Email
                        </label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-600 focus:outline-none">

                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- PASSWORD --}}
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">
                            Password
                            <span class="text-xs text-gray-400">(kosongkan jika tidak diubah)</span>
                        </label>
                        <input type="password" name="password"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-600 focus:outline-none">

                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- PASSWORD CONFIRM --}}
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">
                            Konfirmasi Password
                        </label>
                        <input type="password" name="password_confirmation"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-600 focus:outline-none">
                    </div>

                    {{-- ROLE --}}
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">
                            Role
                        </label>
                        <select name="role" required
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-600 focus:outline-none">
                            <option value="" disabled>-- Select Role --</option>
                            <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>Admin</option>
                            <option value="user" {{ $user->role === 'user' ? 'selected' : '' }}>User</option>
                        </select>

                        @error('role')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- SHOW PASSWORD --}}
                    <div class="flex items-center gap-2 mt-7">
                        <input type="checkbox" id="showPassword" onclick="togglePasswords()"
                            class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500">
                        <label for="showPassword" class="text-sm text-gray-700">
                            Show Password
                        </label>
                    </div>
                </div>

                {{-- ACTION BUTTON --}}
                <div class="flex justify-end gap-3 pt-6 border-t">
                    <a href="{{ route('users.index') }}"
                        class="px-5 py-2 text-sm font-medium text-gray-700 transition bg-gray-200 rounded-lg hover:bg-gray-300">
                        Back
                    </a>

                    <button type="submit"
                        class="px-6 py-2 text-sm font-semibold text-white transition bg-blue-600 rounded-lg hover:bg-blue-700">
                        Update User
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function togglePasswords() {
            document.querySelectorAll("input[type='password']").forEach(field => {
                field.type = field.type === "password" ? "text" : "password";
            });
        }
    </script>
@endsection
