@extends('layout.master')

@section('content')
    <div class="w-full max-w-3xl mx-auto space-y-6">

        {{-- HEADER --}}
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl text-slate-900">
                    Add Failure Report
                </h1>
                <p class="text-xs sm:text-sm font-medium text-slate-500 mt-0.5">
                    Register technical breakdown logs and field component failures.
                </p>
            </div>
            <a href="{{ route('report.index') }}"
                class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold transition-all bg-white border text-slate-600 border-slate-200 rounded-xl hover:bg-slate-50 active:scale-95 shrink-0">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Back</span>
            </a>
        </div>

        {{-- FORM CARD --}}
        <div class="overflow-hidden bg-white border shadow-sm border-slate-200/80 rounded-2xl">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h2 class="text-xs font-extrabold tracking-wider uppercase text-slate-700">Breakdown Details</h2>
                @if (isset($selectedStock))
                    <span
                        class="px-2.5 py-1 text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200/60 rounded-lg uppercase">
                        From Damaged Queue
                    </span>
                @endif
            </div>

            <form action="{{ route('report.store') }}" method="POST" enctype="multipart/form-data"
                class="p-6 space-y-5 sm:p-8">
                @csrf

                {{-- Hidden Stock ID --}}
                <input type="hidden" name="stock_id" value="{{ $selectedStock->id ?? '' }}">

                {{-- ATTENDANT --}}
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                        Attendant / Reporter
                    </label>
                    <input type="text" name="attendant" value="{{ old('attendant', Auth::user()->name) }}"
                        placeholder="Reporter name..."
                        class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-rose-500/10 focus:border-rose-500 outline-none transition-all bg-slate-50 focus:bg-white @error('attendant') border-rose-500 @enderror"
                        required>
                    @error('attendant')
                        <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

                    {{-- SITE MACHINE --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Site Machine
                        </label>
                        <select name="site_machine" id="site_machine"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-rose-500/10 focus:border-rose-500 outline-none transition-all bg-slate-50 focus:bg-white @error('site_machine') border-rose-500 @enderror"
                            required>

                            @php
                                $currentSite = old('site_machine', $selectedStock->site->slug ?? '');
                            @endphp

                            <option value="" disabled {{ empty($currentSite) ? 'selected' : '' }}>-- Select Machine
                                Site --</option>
                            @foreach ($sites as $site)
                                <option value="{{ $site->slug }}" {{ $currentSite == $site->slug ? 'selected' : '' }}>
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
                            Failure Date
                        </label>
                        <input type="date" name="failure_date" value="{{ old('failure_date', now()->format('Y-m-d')) }}"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-rose-500/10 focus:border-rose-500 outline-none transition-all bg-slate-50 focus:bg-white @error('failure_date') border-rose-500 @enderror"
                            required>
                        @error('failure_date')
                            <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

                    {{-- FAILED SUB-SYSTEM --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Failed Sub-System
                        </label>
                        @php
                            $defaultSubsystem = isset($selectedStock) ? $selectedStock->sparepart->type ?? '' : '';
                        @endphp
                        <textarea name="failed_subsystem" rows="3"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-rose-500/10 focus:border-rose-500 outline-none transition-all bg-slate-50 focus:bg-white @error('failed_subsystem') border-rose-500 @enderror"
                            placeholder="e.g. CCR Subsystem, Power Pack, etc.">{{ old('failed_subsystem', $defaultSubsystem) }}</textarea>

                        @error('failed_subsystem')
                            <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- FAILURE PHENOMENON --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Failure Phenomenon
                        </label>
                        @php
                            $defaultPhenomenon = isset($selectedStock)
                                ? 'Component breakdown: ' .
                                    $selectedStock->sparepart->item_name .
                                    ' (SN: ' .
                                    ($selectedStock->sparepart->serial_number ?? '-') .
                                    ')'
                                : '';
                        @endphp
                        <textarea name="failure_phenomenon" rows="3"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-rose-500/10 focus:border-rose-500 outline-none transition-all bg-slate-50 focus:bg-white @error('failure_phenomenon') border-rose-500 @enderror"
                            placeholder="Explain breakdown symptoms & observations">{{ old('failure_phenomenon', $defaultPhenomenon) }}</textarea>

                        @error('failure_phenomenon')
                            <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                </div>

                {{-- TS PROCEDURE --}}
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                        Troubleshoot Procedure
                    </label>
                    @php
                        $defaultTs = isset($selectedStock) ? $selectedStock->sparepart->note ?? '' : '';
                    @endphp
                    <textarea name="ts_procedure" rows="3" placeholder="Steps taken for repair or part replacement..."
                        class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-rose-500/10 focus:border-rose-500 outline-none transition-all bg-slate-50 focus:bg-white @error('ts_procedure') border-rose-500 @enderror">{{ old('ts_procedure', $defaultTs) }}</textarea>
                    @error('ts_procedure')
                        <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- IMAGE --}}
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                        Image Evidence
                    </label>
                    <input type="file" name="image" accept="image/png,image/jpeg,image/webp"
                        class="block w-full text-xs transition-colors sm:text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-rose-50 file:text-rose-700 hover:file:bg-rose-100">
                    @error('image')
                        <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- ACTION --}}
                <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
                    <a href="{{ route('report.index') }}"
                        class="px-4 py-2.5 text-xs font-bold text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 transition-colors">
                        Cancel
                    </a>
                    <button type="submit"
                        class="px-6 py-2.5 text-xs font-bold text-white bg-rose-600 rounded-xl hover:bg-rose-700 shadow-md shadow-rose-600/20 active:scale-95 transition-all">
                        Save Report
                    </button>
                </div>

            </form>
        </div>
    </div>
@endsection
