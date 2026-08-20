@extends('layout.master')

@section('title', 'My Profile')

@section('content')
    <div class="w-full space-y-6">

        {{-- 1. PROFILE HEADER CARD --}}
        <div class="p-6 bg-white border shadow-xs sm:p-8 border-slate-200/80 rounded-3xl">
            <div class="flex flex-col items-center gap-6 sm:flex-row sm:items-start">
                <div class="relative w-20 h-20 sm:w-24 sm:h-24 shrink-0">
                    <img src="{{ asset('img/profile.png') }}" alt="Profile Picture"
                        class="object-cover w-full h-full border shadow-xs border-slate-200/80 rounded-2xl">
                </div>

                <div class="space-y-1.5 text-center sm:text-left">
                    <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">
                        {{ auth()->user()->name }}
                    </h1>
                    <div class="flex flex-wrap items-center justify-center gap-2 pt-1 sm:justify-start">
                        <span
                            class="px-3 py-1 text-[10px] font-black uppercase tracking-widest bg-blue-50 text-blue-700 border border-blue-200/60 rounded-full">
                            {{ str_replace('_', ' ', auth()->user()->role) }}
                        </span>
                        @if (auth()->user()->site)
                            <span
                                class="px-3 py-1 text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-600 border border-slate-200 rounded-full">
                                <i
                                    class="mr-1 fa-solid fa-location-dot text-slate-400"></i>{{ auth()->user()->site->machine_name }}
                            </span>
                        @else
                            <span
                                class="px-3 py-1 text-[10px] font-bold uppercase tracking-wider bg-purple-50 text-purple-700 border border-purple-200/60 rounded-full">
                                Head Office / All Sites
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. DETAILS CARD --}}
        <div class="overflow-hidden bg-white border shadow-xs border-slate-200/80 rounded-3xl">
            {{-- HEADER BAR --}}
            <div
                class="flex flex-col justify-between gap-3 p-6 border-b sm:flex-row sm:items-center border-slate-100 bg-slate-50/50">
                <div>
                    <h2 class="text-xs font-extrabold tracking-wider uppercase text-slate-700">User Account Details</h2>
                    <p class="text-xs font-medium text-slate-500 mt-0.5">View personal credentials and site permissions.</p>
                </div>
                <div class="flex items-center gap-2.5 shrink-0">
                    @if (Auth::user()?->role === 'superadmin')
                        <a href="{{ route('profile.profileList') }}"
                            class="px-4 py-2.5 text-xs font-bold transition-all bg-white border text-slate-700 border-slate-200 rounded-xl hover:bg-slate-50 shadow-2xs active:scale-95">
                            <i class="mr-1.5 fa-solid fa-users-gear text-slate-400"></i> Manage Accounts
                        </a>
                    @endif

                    <a href="{{ route('profile.profileEdit', ['id' => auth()->id()]) }}"
                        class="px-4 py-2.5 text-xs font-bold text-white transition-all bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-600/20 rounded-xl active:scale-95">
                        <i class="mr-1.5 fa-solid fa-pen-to-square"></i> Edit Profile
                    </a>
                </div>
            </div>

            {{-- BODY --}}
            <div class="p-6 space-y-4 sm:p-8">
                {{-- Full Name --}}
                <div class="grid items-center grid-cols-1 gap-2 py-3 border-b md:grid-cols-3 border-slate-100">
                    <label class="text-xs font-bold tracking-wider uppercase text-slate-500">Full Name</label>
                    <p class="text-sm font-bold md:col-span-2 text-slate-900">
                        {{ auth()->user()->name }}
                    </p>
                </div>

                {{-- Username --}}
                <div class="grid items-center grid-cols-1 gap-2 py-3 border-b md:grid-cols-3 border-slate-100">
                    <label class="text-xs font-bold tracking-wider uppercase text-slate-500">Username</label>
                    <p class="font-mono text-sm font-bold md:col-span-2 text-slate-800">
                        @ {{ auth()->user()->username ?? auth()->user()->name }}
                    </p>
                </div>

                {{-- Email --}}
                <div class="grid items-center grid-cols-1 gap-2 py-3 border-b md:grid-cols-3 border-slate-100">
                    <label class="text-xs font-bold tracking-wider uppercase text-slate-500">Email Address</label>
                    <p class="text-sm font-bold md:col-span-2 text-slate-900">
                        {{ auth()->user()->email }}
                    </p>
                </div>

                {{-- Role --}}
                <div class="grid items-center grid-cols-1 gap-2 py-3 border-b md:grid-cols-3 border-slate-100">
                    <label class="text-xs font-bold tracking-wider uppercase text-slate-500">User Role</label>
                    <p class="text-sm font-bold tracking-wide uppercase md:col-span-2 text-slate-900">
                        {{ str_replace('_', ' ', auth()->user()->role) }}
                    </p>
                </div>

                {{-- Site Assignment --}}
                <div class="grid items-center grid-cols-1 gap-2 py-3 md:grid-cols-3">
                    <label class="text-xs font-bold tracking-wider uppercase text-slate-500">Site Assignment</label>
                    <p class="text-sm font-bold md:col-span-2 text-slate-900">
                        {{ auth()->user()->site->machine_name ?? 'All Sites (Head Office)' }}
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
