@extends('layout.master')

@section('content')
    {{-- Tambahkan variabel 'showPassword' dan 'showConfirmPassword' ke x-data --}}
    <div class="min-h-screen px-6 py-6" x-data="{
        role: '{{ old('role') }}',
        password: '',
        password_confirm: '',
        showPassword: false,
        showConfirmPassword: false
    }">

        {{-- PAGE HEADER --}}
        <div class="p-6 mb-6 bg-white shadow-lg rounded-2xl">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-blue-100 rounded-lg">
                    <i class="text-xl text-blue-600 fa-solid fa-user-plus"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Add New User</h1>
                    <p class="text-sm text-gray-500">Register a new user account into the Nuctech Warehouse system.</p>
                </div>
            </div>
        </div>

        {{-- FORM WRAPPER --}}
        <div class="max-w-4xl bg-white shadow-md rounded-2xl">

            <div class="px-6 py-4 border-b">
                <h2 class="text-lg font-semibold text-gray-700">Account Information</h2>
            </div>

            <form action="{{ route('users.store') }}" method="POST" class="p-6 space-y-6">
                @csrf

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

                    {{-- NAME, USERNAME, EMAIL, ROLE (Tetap sama seperti sebelumnya) --}}
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">Full Name</label>
                        <input type="text" name="name" value="{{ old('name') }}" placeholder="e.g., John Doe"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-600 focus:outline-none @error('name') border-red-500 @enderror">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">Username</label>
                        <input type="text" name="username" value="{{ old('username') }}" placeholder="username123"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-600 focus:outline-none @error('username') border-red-500 @enderror">
                        @error('username')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">Email Address</label>
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="user@nuctech.com"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-600 focus:outline-none @error('email') border-red-500 @enderror">
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">User Role</label>
                        <select name="role" x-model="role"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-600 focus:outline-none @error('role') border-red-500 @enderror">
                            <option value="">-- Select Role --</option>
                            <option value="superadmin">Superadmin (Head Office)</option>
                            <option value="admin_site">Site Admin (Branch/Location)</option>
                        </select>
                    </div>

                    {{-- SITE ASSIGNMENT --}}
                    <div x-show="role === 'admin_site'" x-transition>
                        <label class="block mb-1 text-sm font-medium text-gray-700">Site Assignment</label>
                        <select name="site_id"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-600 focus:outline-none @error('site_id') border-red-500 @enderror">
                            <option value="">-- Select Site Location --</option>
                            @foreach ($sites as $site)
                                <option value="{{ $site->id }}">{{ $site->machine_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="hidden md:block" x-show="role !== 'admin_site'"></div>

                    {{-- PASSWORD WITH SHOW FEATURE --}}
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">Password</label>
                        <div class="relative">
                            <input :type="showPassword ? 'text' : 'password'" name="password" x-model="password"
                                placeholder="Minimum 6 characters"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-600 focus:outline-none @error('password') border-red-500 @enderror">
                            <button type="button" @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">
                                <i class="fa-solid" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- CONFIRM PASSWORD WITH MATCH FEATURE --}}
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">Confirm Password</label>
                        <div class="relative">
                            <input :type="showConfirmPassword ? 'text' : 'password'" name="password_confirmation"
                                x-model="password_confirm" placeholder="Repeat password"
                                class="w-full px-4 py-2 transition-colors border rounded-lg focus:ring-2 focus:outline-none"
                                :class="password_confirm === '' ? 'border-gray-300' : (password === password_confirm ?
                                    'border-green-500 focus:ring-green-200' : 'border-red-500 focus:ring-red-200')">
                            <button type="button" @click="showConfirmPassword = !showConfirmPassword"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">
                                <i class="fa-solid" :class="showConfirmPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                        {{-- Real-time validation message --}}
                        <template x-if="password_confirm !== ''">
                            <p class="mt-1 text-xs"
                                :class="password === password_confirm ? 'text-green-600' : 'text-red-600'">
                                <span
                                    x-text="password === password_confirm ? '✓ Passwords match' : '✗ Passwords do not match'"></span>
                            </p>
                        </template>
                    </div>
                </div>

                {{-- ACTION BUTTONS --}}
                <div class="flex justify-end gap-3 pt-6 border-t">
                    <a href="{{ route('users.index') }}"
                        class="px-5 py-2 text-sm font-medium text-gray-700 transition bg-gray-100 rounded-lg hover:bg-gray-200">
                        Cancel
                    </a>

                    <button type="submit" {{-- Disable button if passwords don't match --}}
                        :disabled="password !== password_confirm || password === ''"
                        class="px-6 py-2 text-sm font-bold text-white transition bg-blue-600 rounded-lg shadow-md hover:bg-blue-700 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="mr-1 fa-solid fa-save"></i> Save User
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
