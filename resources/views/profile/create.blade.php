@extends('layout.master')

@section('title', 'Add New User')

@section('content')
    <div class="w-full max-w-4xl mx-auto space-y-6" x-data="{
        role: '{{ old('role') }}',
        password: '',
        password_confirm: '',
        showPassword: false,
        showConfirmPassword: false
    }">

        {{-- 1. HEADER CARD --}}
        <div class="p-6 bg-white border shadow-xs sm:p-8 border-slate-200/80 rounded-3xl">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <nav class="flex items-center gap-2 mb-1.5 text-xs font-bold tracking-wider text-slate-400 uppercase">
                        <a href="{{ route('profile.profileList') }}" class="transition-colors hover:text-blue-600">User
                            Management</a>
                        <i class="fa-solid fa-chevron-right text-[9px]"></i>
                        <span class="font-extrabold text-blue-600">Create Account</span>
                    </nav>
                    <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl text-slate-900">
                        Add New User
                    </h1>
                    <p class="mt-1 text-xs font-medium sm:text-sm text-slate-500">
                        Register a new system user account and assign site permissions.
                    </p>
                </div>
                <a href="{{ route('profile.profileList') }}"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-all rounded-xl active:scale-95 shrink-0">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Back</span>
                </a>
            </div>
        </div>

        {{-- 2. FORM CARD --}}
        <div class="overflow-hidden bg-white border shadow-xs border-slate-200/80 rounded-3xl">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h2 class="text-xs font-extrabold tracking-wider uppercase text-slate-700">Account Information</h2>
            </div>

            <form action="{{ route('profile.store') }}" method="POST" class="p-6 space-y-6 sm:p-8">
                @csrf

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                    {{-- FULL NAME --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Full Name <span
                                class="text-rose-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" placeholder="e.g. John Doe"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 placeholder-slate-400 @error('name') border-rose-500 @enderror"
                            required>
                        @error('name')
                            <p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- USERNAME --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Username <span
                                class="text-rose-500">*</span></label>
                        <input type="text" name="username" value="{{ old('username') }}" placeholder="e.g. johndoe123"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 placeholder-slate-400 @error('username') border-rose-500 @enderror"
                            required>
                        @error('username')
                            <p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- EMAIL --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Email Address <span
                                class="text-rose-500">*</span></label>
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="user@nuctech.com"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 placeholder-slate-400 @error('email') border-rose-500 @enderror"
                            required>
                        @error('email')
                            <p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- ROLE --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">User Role <span
                                class="text-rose-500">*</span></label>
                        <select name="role" x-model="role"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 @error('role') border-rose-500 @enderror"
                            required>
                            <option value="">-- Select Role --</option>
                            <option value="superadmin">Superadmin (Head Office)</option>
                            <option value="admin_site">Site Admin (Branch Location)</option>
                            <option value="team_leader">Team Leader</option>
                            <option value="station_master">Station Master</option>
                            <option value="manager">Manager</option>
                        </select>
                        @error('role')
                            <p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- SITE ASSIGNMENT --}}
                    <div x-show="role === 'admin_site'" x-transition class="space-y-1.5 md:col-span-2">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Site Assignment <span
                                class="text-rose-500">*</span></label>
                        <select name="site_id"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 @error('site_id') border-rose-500 @enderror">
                            <option value="">-- Select Site Location --</option>
                            @foreach ($sites as $site)
                                <option value="{{ $site->id }}">{{ $site->machine_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- PASSWORD --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Password <span
                                class="text-rose-500">*</span></label>
                        <div class="relative">
                            <input :type="showPassword ? 'text' : 'password'" name="password" x-model="password"
                                placeholder="Minimum 6 characters"
                                class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 @error('password') border-rose-500 @enderror"
                                required>
                            <button type="button" @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600">
                                <i class="fa-solid" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- CONFIRM PASSWORD --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Confirm Password
                            <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <input :type="showConfirmPassword ? 'text' : 'password'" name="password_confirmation"
                                x-model="password_confirm" placeholder="Repeat password"
                                class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium border rounded-xl focus:ring-4 focus:outline-none transition-all bg-slate-50 focus:bg-white text-slate-800"
                                :class="password_confirm === '' ? 'border-slate-200' : (password === password_confirm ?
                                    'border-emerald-500 focus:ring-emerald-200/50' :
                                    'border-rose-500 focus:ring-rose-200/50')"
                                required>
                            <button type="button" @click="showConfirmPassword = !showConfirmPassword"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600">
                                <i class="fa-solid" :class="showConfirmPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                        <template x-if="password_confirm !== ''">
                            <p class="mt-1 text-xs font-bold"
                                :class="password === password_confirm ? 'text-emerald-600' : 'text-rose-600'">
                                <span
                                    x-text="password === password_confirm ? '✓ Passwords match' : '✗ Passwords do not match'"></span>
                            </p>
                        </template>
                    </div>
                </div>

                {{-- ACTION BUTTONS --}}
                <div class="flex items-center justify-between pt-6 border-t border-slate-100">
                    <div class="text-xs font-medium text-slate-400">
                        Asterisk (<span class="text-rose-500">*</span>) fields are required.
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('profile.profileList') }}"
                            class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-800 transition-colors">
                            Cancel
                        </a>
                        <button type="submit" :disabled="password !== password_confirm || password === ''"
                            class="px-6 py-2.5 text-xs font-bold text-white transition-all bg-blue-600 hover:bg-blue-700 rounded-xl shadow-md shadow-blue-600/20 active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="mr-1.5 fa-solid fa-floppy-disk"></i> Save User
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
