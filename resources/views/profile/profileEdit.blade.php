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

        {{-- 1. HEADER CARD --}}
        <div class="p-6 bg-white border shadow-xs sm:p-8 border-slate-200/80 rounded-3xl">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <nav class="flex items-center gap-2 mb-1.5 text-xs font-bold tracking-wider text-slate-400 uppercase">
                        <a href="{{ route('profile.profile') }}" class="transition-colors hover:text-blue-600">User
                            Profile</a>
                        <i class="fa-solid fa-chevron-right text-[9px]"></i>
                        <span class="font-extrabold text-blue-600">Update Credentials</span>
                    </nav>
                    <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl text-slate-900">
                        Edit User Account
                    </h1>
                    <p class="mt-1 text-xs font-medium sm:text-sm text-slate-500">
                        Update credentials and account permissions for <span
                            class="font-bold text-blue-600">{{ $user->name }}</span>
                    </p>
                </div>
                <a href="{{ route('profile.profile') }}"
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

            <form action="{{ route('profile.profileUpdate', $user->id) }}" method="POST" class="p-6 space-y-6 sm:p-8">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                    {{-- FULL NAME --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Full Name <span
                                class="text-rose-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 @error('name') border-rose-500 @enderror"
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
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 @error('username') border-rose-500 @enderror"
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
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 @error('email') border-rose-500 @enderror"
                            required>
                        @error('email')
                            <p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- ROLE --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">User Role</label>
                        <select name="role" x-model="role"
                            {{ auth()->user()?->role !== 'superadmin' ? 'disabled' : '' }}
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 @error('role') border-rose-500 @enderror {{ auth()->user()?->role !== 'superadmin' ? 'bg-slate-100 text-slate-500 cursor-not-allowed border-dashed' : 'cursor-pointer' }}">
                            <option value="superadmin" {{ $user->role === 'superadmin' ? 'selected' : '' }}>Superadmin
                                (Head Office)</option>
                            <option value="employee_role" {{ $user->role === 'employee_role' ? 'selected' : '' }}>Site
                                Admin
                                (Branch Location)</option>
                            <option value="team_leader" {{ $user->role === 'team_leader' ? 'selected' : '' }}>Team Leader
                            </option>
                            <option value="station_master" {{ $user->role === 'station_master' ? 'selected' : '' }}>Station
                                Master</option>
                            <option value="manager" {{ $user->role === 'manager' ? 'selected' : '' }}>Manager</option>
                            <option value="administration" {{ $user->role === 'administration' ? 'selected' : '' }}>
                                Adminstration</option>

                        </select>

                        @if (auth()->user()?->role !== 'superadmin')
                            <input type="hidden" name="role" value="{{ $user->role }}">
                        @endif
                    </div>

                    {{-- SITE ASSIGNMENT --}}
                    <div x-show="role === 'team_leader'" x-transition class="space-y-1.5 md:col-span-2">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Site
                            Assignment</label>
                        <select name="site_id" {{ auth()->user()?->role !== 'superadmin' ? 'disabled' : '' }}
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 @error('site_id') border-rose-500 @enderror {{ auth()->user()?->role !== 'superadmin' ? 'bg-slate-100 text-slate-500 cursor-not-allowed border-dashed' : 'cursor-pointer' }}">
                            <option value="">-- Select Site Location --</option>
                            @foreach ($sites as $site)
                                <option value="{{ $site->id }}" {{ $user->site_id == $site->id ? 'selected' : '' }}>
                                    {{ $site->machine_name }}
                                </option>
                            @endforeach
                        </select>

                        @if (auth()->user()?->role !== 'superadmin')
                            <input type="hidden" name="site_id" value="{{ $user->site_id }}">
                        @endif
                    </div>

                    {{-- NEW PASSWORD --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            New Password
                            <span class="text-[10px] font-normal text-slate-400 lowercase">(leave blank to keep
                                current)</span>
                        </label>
                        <div class="relative">
                            <input :type="showPassword ? 'text' : 'password'" name="password" x-model="password"
                                placeholder="Min. 6 characters"
                                class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 @error('password') border-rose-500 @enderror">
                            <button type="button" @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer text-slate-400 hover:text-slate-600">
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
                                class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium border rounded-xl focus:ring-4 focus:outline-none transition-all bg-slate-50 focus:bg-white text-slate-800"
                                :class="password_confirm === '' ? 'border-slate-200' : (password === password_confirm ?
                                    'border-emerald-500 focus:ring-emerald-200/50' :
                                    'border-rose-500 focus:ring-rose-200/50')">
                            <button type="button" @click="showConfirmPassword = !showConfirmPassword"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer text-slate-400 hover:text-slate-600">
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
                    <div class="flex items-center gap-3">
                        <a href="{{ route('profile.profile') }}"
                            class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-800 transition-colors">
                            Cancel
                        </a>
                        <button type="submit" x-bind:disabled="!isValid()"
                            class="px-6 py-2.5 text-xs font-bold text-white transition-all bg-blue-600 hover:bg-blue-700 rounded-xl shadow-md shadow-blue-600/20 active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer">
                            <i class="mr-1.5 fa-solid fa-floppy-disk"></i> Update User
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        if (window.up && window.Alpine) {
            up.compiler('[x-data]', function(element) {
                Alpine.initTree(element);
            });
        }
    </script>
@endpush
