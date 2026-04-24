@extends('layout.master')

@section('content')
    <div class="min-h-screen px-6 py-8 bg-gray-50/50">

        {{-- BREADCRUMB & HEADER --}}
        <div class="max-w-4xl mx-auto mb-8">
            <nav class="flex mb-3 text-xs font-bold tracking-widest text-gray-400 uppercase">
                <a href="{{ route('site.index') }}" class="transition-colors hover:text-blue-600">Machine Sites</a>
                <span class="mx-2">/</span>
                <span class="text-blue-600">Update Data</span>
            </nav>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-extrabold tracking-tight text-gray-900">Edit Site Profile</h1>
                    <p class="mt-1 text-sm text-gray-500">Modify site identification and machine specifications.</p>
                </div>
            </div>
        </div>

        {{-- FORM CARD --}}
        <div class="max-w-4xl mx-auto">
            <div class="overflow-hidden bg-white shadow-sm ring-1 ring-gray-200 rounded-3xl">

                {{-- FORM HEADER --}}
                <div class="flex items-center justify-between px-8 py-5 border-b border-gray-100 bg-gray-50/50">
                    <h2 class="text-sm font-bold tracking-wider text-gray-700 uppercase">Site Information</h2>
                    <span class="px-3 py-1 text-[10px] font-black bg-blue-100 text-blue-600 rounded-full uppercase">
                        ID: {{ $user->id }}
                    </span>
                </div>

                {{-- FORM BODY --}}
                <form action="{{ route('site.update', $user->id) }}" method="POST" class="p-8">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 gap-x-8 gap-y-6 md:grid-cols-2">

                        {{-- CODE SITE --}}
                        <div>
                            <label class="flex items-center block gap-2 mb-2 text-sm font-bold text-gray-700">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                </svg>
                                Code Site
                            </label>
                            <input type="text" name="code" value="{{ old('code', $user->code) }}" required
                                class="w-full px-4 py-3 border border-gray-200 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-600 focus:outline-none transition-all font-mono text-sm uppercase tracking-wider @error('code') border-red-500 @enderror"
                                placeholder="e.g. IDN_FS6000">

                            @error('code')
                                <p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- MACHINE NAME --}}
                        <div>
                            <label class="block mb-2 text-sm font-bold text-gray-700">
                                Machine Name
                            </label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                                class="w-full px-4 py-3 border border-gray-200 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-600 focus:outline-none transition-all font-semibold @error('name') border-red-500 @enderror"
                                placeholder="e.g. FS6000 Jakarta HQ">

                            @error('name')
                                <p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- MACHINE TYPE --}}
                        <div class="md:col-span-2">
                            <label class="flex items-center block gap-2 mb-2 text-sm font-bold text-gray-700">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                                </svg>
                                Machine Type
                            </label>
                            <input type="text" name="machine_type" value="{{ old('machine_type', $user->machine_type) }}"
                                required
                                class="w-full px-4 py-3 border border-gray-200 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-600 focus:outline-none transition-all @error('machine_type') border-red-500 @enderror"
                                placeholder="e.g. Industrial Printer">

                            @error('machine_type')
                                <p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- ACTION BUTTONS --}}
                    <div class="flex items-center justify-end gap-4 pt-8 mt-10 border-t border-gray-100">
                        <a href="{{ route('site.index') }}"
                            class="px-6 py-3 text-sm font-bold text-gray-500 transition-colors hover:text-gray-700">
                            Discard Changes
                        </a>

                        <button type="submit"
                            class="px-8 py-3 text-sm font-bold text-white transition-all bg-blue-600 shadow-xl rounded-2xl hover:bg-blue-700 shadow-blue-100 active:scale-95">
                            Save Updates
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
