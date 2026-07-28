@extends('layout.master')

@section('title', 'Edit User Account')

@section('content')
    <div class="w-full max-w-4xl mx-auto space-y-6" x-data="{
        role: '{{ old('role', $user->role) }}',
        password: '',
        password_confirm: '',
        showPassword: false,
        showConfirmPassword: false,
        isValid() {
            if (this.password === '') return true;
            return this.password.length >= 6 && this.password === this.password_confirm;
        }
    }">

        {{-- PAGE HEADER --}}
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl text-slate-900">Edit User Account</h1>
                <p class="text-xs sm:text-sm font-medium text-slate-500 mt-0.5">Update credentials for <span
                        class="font-bold text-blue-600">{{ $user->name }}</span></p>
            </div>
            <a href="{{ route('profile.profile') }}"
                class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold transition-all bg-white border text-slate-600 border-slate-200 rounded-xl hover:bg-slate-50 active:scale-95 shrink-0">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Back</span>
            </a>
        </div>

        {{-- FORM CARD --}}
        <div class="overflow-hidden bg-white border shadow-sm border-slate-200/80 rounded-2xl sm:rounded-3xl">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h2 class="text-xs font-extrabold tracking-wider uppercase text-slate-700">Account Information</h2>
            </div>

            <form action="{{ route('profile.profileUpdate', $user->id) }}" method="POST" class="p-6 space-y-6 sm:p-8">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                    {{-- FULL NAME --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Full Name <span
                                class="text-rose-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 @error('name') border-rose-500 @enderror"
                            required>
                        @error('name')
                            <p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- USERNAME --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Username <span
                                class="text-rose-500">*</span></label>
                        <input type="text" name="username" value="{{ old('username', $user->username) }}"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 @error('username') border-rose-500 @enderror"
                            required>
                        @error('username')
                            <p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- EMAIL --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Email Address <span
                                class="text-rose-500">*</span></label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 @error('email') border-rose-500 @enderror"
                            required>
                        @error('email')
                            <p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- ROLE --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">User Role</label>
                        <select name="role" x-model="role" {{ auth()->user()->role !== 'superadmin' ? 'disabled' : '' }}
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 @error('role') border-rose-500 @enderror {{ auth()->user()->role !== 'superadmin' ? 'bg-slate-100 text-slate-500 cursor-not-allowed border-dashed' : '' }}">
                            <option value="superadmin" {{ $user->role === 'superadmin' ? 'selected' : '' }}>Superadmin
                                (Head Office)</option>
                            <option value="admin_site" {{ $user->role === 'admin_site' ? 'selected' : '' }}>Site Admin
                                (Branch Location)</option>
                            <option value="team_leader" {{ $user->role === 'team_leader' ? 'selected' : '' }}>Team Leader
                            </option>
                            <option value="station_master" {{ $user->role === 'station_master' ? 'selected' : '' }}>Station
                                Master</option>
                            <option value="manager" {{ $user->role === 'manager' ? 'selected' : '' }}>Manager</option>
                        </select>

                        @if (auth()->user()->role !== 'superadmin')
                            <input type="hidden" name="role" value="{{ $user->role }}">
                        @endif
                    </div>

                    {{-- SITE ASSIGNMENT --}}
                    <div x-show="role === 'admin_site'" x-transition class="space-y-1.5 md:col-span-2">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Site
                            Assignment</label>
                        <select name="site_id" {{ auth()->user()->role !== 'superadmin' ? 'disabled' : '' }}
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 @error('site_id') border-rose-500 @enderror {{ auth()->user()->role !== 'superadmin' ? 'bg-slate-100 text-slate-500 cursor-not-allowed border-dashed' : '' }}">
                            <option value="">-- Select Site Location --</option>
                            @foreach ($sites as $site)
                                <option value="{{ $site->id }}" {{ $user->site_id == $site->id ? 'selected' : '' }}>
                                    {{ $site->machine_name }}
                                </option>
                            @endforeach
                        </select>

                        @if (auth()->user()->role !== 'superadmin')
                            <input type="hidden" name="site_id" value="{{ $user->site_id }}">
                        @endif
                    </div>

                    {{-- NEW PASSWORD --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            New Password
                            <span class="text-[10px] font-medium text-slate-400 lowercase">(leave blank to keep
                                current)</span>
                        </label>
                        <div class="relative">
                            <input :type="showPassword ? 'text' : 'password'" name="password" x-model="password"
                                placeholder="Min. 6 characters"
                                class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 @error('password') border-rose-500 @enderror">
                            <button type="button" @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600">
                                <i class="fa-solid" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- CONFIRM NEW PASSWORD --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Confirm New
                            Password</label>
                        <div class="relative">
                            <input :type="showConfirmPassword ? 'text' : 'password'" name="password_confirmation"
                                x-model="password_confirm" placeholder="Repeat new password"
                                class="w-full px-3.5 py-2.5 text-xs sm:text-sm border rounded-xl focus:ring-4 focus:outline-none transition-all bg-slate-50 focus:bg-white text-slate-800"
                                :class="password_confirm === '' ? 'border-slate-200' : (password === password_confirm ?
                                    'border-emerald-500 focus:ring-emerald-200/50' :
                                    'border-rose-500 focus:ring-rose-200/50')">
                            <button type="button" @click="showConfirmPassword = !showConfirmPassword"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600">
                                <i class="fa-solid" :class="showConfirmPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                        <template x-if="password !== ''">
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
                    <div class="flex items-center gap-2.5">
                        <a href="{{ route('profile.profile') }}"
                            class="px-4 py-2.5 text-xs font-bold text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 transition-colors">
                            Cancel
                        </a>
                        <button type="submit" x-bind:disabled="!isValid()"
                            class="px-6 py-2.5 text-xs font-bold text-white transition-all bg-blue-600 rounded-xl shadow-md shadow-blue-600/20 hover:bg-blue-700 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="mr-1 fa-solid fa-save"></i> Update User
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
