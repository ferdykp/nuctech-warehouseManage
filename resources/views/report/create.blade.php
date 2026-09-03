@extends('layout.master')

@section('title', 'Create Failure Report')

@section('content')
    <div class="w-full max-w-3xl mx-auto space-y-6">

        {{-- 1. HEADER CARD --}}
        <div class="p-6 bg-white border shadow-xs sm:p-8 border-slate-200/80 rounded-3xl">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <nav class="flex items-center gap-2 mb-1.5 text-xs font-bold tracking-wider text-slate-400 uppercase">
                        <a href="{{ route('report.index') }}" class="transition-colors hover:text-rose-600">Failure
                            Reports</a>
                        <i class="fa-solid fa-chevron-right text-[9px]"></i>
                        <span class="font-extrabold text-rose-600">Create Log Entry</span>
                    </nav>
                    <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl text-slate-900">
                        Add Failure Report
                    </h1>
                    <p class="mt-1 text-xs font-medium sm:text-sm text-slate-500">
                        Record component breakdown logs, field observations, and maintenance actions.
                    </p>
                </div>
                <a href="{{ route('report.index') }}"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-all rounded-xl active:scale-95 shrink-0 cursor-pointer">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Back</span>
                </a>
            </div>
        </div>

        {{-- 2. FORM CARD --}}
        <div class="overflow-hidden bg-white border shadow-xs border-slate-200/80 rounded-3xl">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h2 class="text-xs font-extrabold tracking-wider uppercase text-slate-700">Report Information</h2>
                <span
                    class="px-3 py-1 text-[10px] font-black bg-rose-50 text-rose-700 border border-rose-200/60 rounded-xl uppercase">
                    New Entry
                </span>
            </div>

            <form action="{{ route('report.store') }}" method="POST" enctype="multipart/form-data"
                class="p-6 space-y-5 sm:p-8">
                @csrf

                {{-- SIMPAN HIDDEN STOCK_ID JIKA DIAKSES DARI QUEUE --}}
                @if (request('stock_id'))
                    <input type="hidden" name="stock_id" value="{{ request('stock_id') }}">
                @endif

                {{-- ATTENDANT --}}
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                        Attendant / Reporter <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="attendant" value="{{ old('attendant', Auth::user()?->name) }}"
                        class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium border border-slate-200 rounded-xl focus:ring-4 focus:ring-rose-500/10 focus:border-rose-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 @error('attendant') border-rose-500 @enderror"
                        required>
                    @error('attendant')
                        <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    {{-- SITE MACHINE --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Site Machine <span class="text-rose-500">*</span>
                        </label>
                        <select name="site_machine" id="site_machine"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium border border-slate-200 rounded-xl focus:ring-4 focus:ring-rose-500/10 focus:border-rose-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 @error('site_machine') border-rose-500 @enderror"
                            required>
                            <option value="" disabled
                                {{ !old('site_machine', $selectedSiteSlug ?? request('stock_site_slug')) ? 'selected' : '' }}>
                                -- Select Site Machine --</option>
                            @foreach ($sites as $site)
                                <option value="{{ $site->slug }}"
                                    {{ old('site_machine', $selectedSiteSlug ?? request('stock_site_slug')) == $site->slug ? 'selected' : '' }}>
                                    {{ $site->machine_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('site_machine')
                            <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- FAILURE DATE --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Failure Date <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" name="failure_date" value="{{ old('failure_date', date('Y-m-d')) }}"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium border border-slate-200 rounded-xl focus:ring-4 focus:ring-rose-500/10 focus:border-rose-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800"
                            required>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    {{-- FAILED SUB SYSTEM --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Failed Sub-System
                        </label>
                        <textarea name="failed_subsystem" rows="3" placeholder="e.g. Conveyor Motor / Sensor Array"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium border border-slate-200 rounded-xl focus:ring-4 focus:ring-rose-500/10 focus:border-rose-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800">{{ old('failed_subsystem', $selectedSubsystem ?? '') }}</textarea>
                    </div>

                    {{-- FAILURE PHENOMENON --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Failure Phenomenon
                        </label>
                        <textarea name="failure_phenomenon" rows="3" placeholder="Describe what happened..."
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium border border-slate-200 rounded-xl focus:ring-4 focus:ring-rose-500/10 focus:border-rose-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800">{{ old('failure_phenomenon') }}</textarea>
                    </div>
                </div>

                {{-- TS PROCEDURE --}}
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                        Troubleshoot Procedure
                    </label>
                    <textarea name="ts_procedure" rows="3" placeholder="Steps taken to diagnose and resolve..."
                        class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium border border-slate-200 rounded-xl focus:ring-4 focus:ring-rose-500/10 focus:border-rose-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800">{{ old('ts_procedure') }}</textarea>
                </div>

                {{-- IMAGE --}}
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                        Image Evidence
                    </label>
                    <input type="file" name="image" accept="image/*"
                        class="block w-full text-xs transition-colors sm:text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-rose-50 file:text-rose-700 hover:file:bg-rose-100 cursor-pointer">
                </div>

                {{-- ACTIONS --}}
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                    <a href="{{ route('report.index') }}"
                        class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-800 transition-colors cursor-pointer">
                        Discard
                    </a>
                    <button type="submit"
                        class="px-6 py-2.5 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-xl shadow-md shadow-rose-600/20 active:scale-[0.98] transition-all cursor-pointer">
                        <i class="mr-1.5 fa-solid fa-floppy-disk"></i> Save Report
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
