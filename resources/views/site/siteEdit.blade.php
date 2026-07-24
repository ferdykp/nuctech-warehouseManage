@extends('layout.master')

@section('content')
    <div class="w-full max-w-3xl mx-auto space-y-6">

        {{-- BREADCRUMB & HEADER --}}
        <div class="flex items-center justify-between gap-3">
            <div>
                <nav class="flex mb-1 text-xs font-bold tracking-widest uppercase text-slate-400">
                    <a href="{{ route('site.index') }}" class="transition-colors hover:text-blue-600">Machine Sites</a>
                    <span class="mx-2">/</span>
                    <span class="text-blue-600">Update Profile</span>
                </nav>
                <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl text-slate-900">Edit Site Profile</h1>
                <p class="text-xs sm:text-sm font-medium text-slate-500 mt-0.5">Modify site identification and machine
                    specifications.</p>
            </div>
            <a href="{{ route('site.index') }}"
                class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold transition-all bg-white border text-slate-600 border-slate-200 rounded-xl hover:bg-slate-50 active:scale-95 shrink-0">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Back</span>
            </a>
        </div>

        {{-- FORM CARD --}}
        <div class="overflow-hidden bg-white border shadow-sm border-slate-200/80 rounded-2xl">

            {{-- FORM HEADER --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h2 class="text-xs font-extrabold tracking-wider uppercase text-slate-700">Site Information</h2>
                <span
                    class="px-2.5 py-1 text-[10px] font-black bg-blue-50 text-blue-700 border border-blue-200/60 rounded-lg uppercase">
                    ID: #{{ str_pad($user->id, 4, '0', STR_PAD_LEFT) }}
                </span>
            </div>

            {{-- FORM BODY --}}
            <form action="{{ route('site.update', $user->id) }}" method="POST" class="p-6 space-y-5 sm:p-8">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

                    {{-- SITE CODE --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Site Code
                        </label>
                        <input type="text" name="code" value="{{ old('code', $user->code) }}" required
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-mono uppercase border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white @error('code') border-rose-500 @enderror"
                            placeholder="e.g. IDN_FS6000">

                        @error('code')
                            <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- MACHINE NAME --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Machine Name
                        </label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white @error('name') border-rose-500 @enderror"
                            placeholder="e.g. FS6000 Jakarta HQ">

                        @error('name')
                            <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- MACHINE TYPE --}}
                    <div class="sm:col-span-2 space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Machine Type
                        </label>
                        <input type="text" name="machine_type" value="{{ old('machine_type', $user->machine_type) }}"
                            required
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white @error('machine_type') border-rose-500 @enderror"
                            placeholder="e.g. Industrial Scanner">

                        @error('machine_type')
                            <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- ACTION BUTTONS --}}
                <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
                    <a href="{{ route('site.index') }}"
                        class="px-4 py-2.5 text-xs font-bold text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 transition-colors">
                        Discard
                    </a>

                    <button type="submit"
                        class="px-6 py-2.5 text-xs font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700 shadow-md shadow-blue-600/20 active:scale-95 transition-all">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
