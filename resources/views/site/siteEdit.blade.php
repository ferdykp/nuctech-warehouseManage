@extends('layout.master')

@section('title', 'Edit Site Profile')

@section('content')
    <div class="w-full max-w-3xl mx-auto space-y-6">

        {{-- 1. HEADER CARD (TERPISAH) --}}
        <div class="p-6 bg-white border shadow-xs sm:p-8 border-slate-200/80 rounded-3xl">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <nav class="flex items-center gap-2 mb-1.5 text-xs font-bold tracking-wider text-slate-400 uppercase">
                        <a href="{{ route('site.index') }}" class="transition-colors hover:text-blue-600">Machine Sites</a>
                        <i class="fa-solid fa-chevron-right text-[9px]"></i>
                        <span class="font-extrabold text-blue-600">Update Profile</span>
                    </nav>
                    <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl text-slate-900">
                        Edit Site Profile
                    </h1>
                    <p class="mt-1 text-xs font-medium sm:text-sm text-slate-500">
                        Modify site identification and machine specifications.
                    </p>
                </div>
                <a href="{{ route('site.index') }}"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-all rounded-xl active:scale-95 shrink-0">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Back</span>
                </a>
            </div>
        </div>

        {{-- 2. FORM CARD --}}
        <div class="overflow-hidden bg-white border shadow-xs border-slate-200/80 rounded-3xl">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h2 class="text-xs font-extrabold tracking-wider uppercase text-slate-700">Site Information</h2>
                <span
                    class="px-3 py-1 text-[10px] font-extrabold bg-blue-50 text-blue-700 border border-blue-200/80 rounded-full uppercase tracking-wide">
                    ID: #{{ str_pad($user->id, 4, '0', STR_PAD_LEFT) }}
                </span>
            </div>

            <form action="{{ route('profile.profileUpdate', $user->id) }}" method="POST" class="p-6 space-y-6 sm:p-8">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

                    {{-- SITE CODE --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Site Code <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="code" value="{{ old('code', $user->code) }}" required
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-mono uppercase font-bold border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 @error('code') border-rose-500 @enderror"
                            placeholder="e.g. IDN_FS6000">

                        @error('code')
                            <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- MACHINE NAME --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Machine Name <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 @error('name') border-rose-500 @enderror"
                            placeholder="e.g. FS6000 Jakarta HQ">

                        @error('name')
                            <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- MACHINE TYPE --}}
                    <div class="sm:col-span-2 space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Machine Type <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="machine_type" value="{{ old('machine_type', $user->machine_type) }}"
                            required
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 @error('machine_type') border-rose-500 @enderror"
                            placeholder="e.g. Industrial Scanner">

                        @error('machine_type')
                            <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- ACTION BUTTONS --}}
                <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                    <div class="text-xs font-medium text-slate-400">
                        Asterisk (<span class="text-rose-500">*</span>) fields are required.
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('site.index') }}"
                            class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-800 transition-colors">
                            Discard
                        </a>
                        <button type="submit"
                            class="px-6 py-2.5 text-xs font-bold text-white transition-all bg-blue-600 hover:bg-blue-700 rounded-xl shadow-md shadow-blue-600/20 active:scale-95">
                            <i class="mr-1.5 fa-solid fa-floppy-disk"></i> Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
