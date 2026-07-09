@extends('layout.master')

@section('content')
    <div class="w-full px-6 py-3">
        <div class="overflow-hidden bg-white shadow-2xl rounded-2xl">

            {{-- HEADER --}}
            <div class="px-8 py-6 border-b bg-gray-50">
                <h4 class="text-2xl font-bold text-gray-800">
                    Add Report
                    @if (isset($selectedStock))
                        <span class="text-sm font-semibold px-2.5 py-1 bg-amber-100 text-amber-800 rounded-full ml-2">Dari
                            Antrean Damage</span>
                    @endif
                </h4>
                <p class="mt-1 text-sm text-gray-500">
                    Form input data report kegagalan mesin lapangan.
                </p>
                <div class="w-24 mt-3 border-b-4 border-red-600 rounded"></div>
            </div>

            {{-- BODY --}}
            <div class="px-8">
                <form action="{{ route('report.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                    @csrf

                    {{-- Hidden Stock ID jika dipicu dari antrean --}}
                    <input type="hidden" name="stock_id" value="{{ $selectedStock->id ?? '' }}">

                    {{-- ATTENDANT --}}
                    <div>
                        <label class="block mb-1 text-sm font-semibold text-gray-700">
                            Attendant / Reporter
                        </label>
                        <input type="text" name="attendant" value="{{ old('attendant', Auth::user()->name) }}"
                            placeholder="Nama petugas pelapor"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-200 focus:outline-none
                        @error('attendant') border-red-500 @enderror"
                            required>
                        @error('attendant')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

                        {{-- SITE MACHINE --}}
                        <div>
                            <label class="block mb-1 text-sm font-semibold text-gray-700">
                                Site Machine
                            </label>
                            <select name="site_machine" id="site_machine"
                                class="w-full px-4 py-2 border rounded-lg bg-white focus:ring-2 focus:ring-blue-200 focus:outline-none
    @error('site_machine') border-red-500 @enderror"
                                required>

                                @php
                                    $currentSite = old('site_machine', $selectedStock->site->slug ?? '');
                                @endphp

                                <option value="" disabled {{ empty($currentSite) ? 'selected' : '' }}>-- Pilih Site --
                                </option>
                                @foreach ($sites as $site)
                                    <option value="{{ $site->slug }}" {{ $currentSite == $site->slug ? 'selected' : '' }}>
                                        {{ $site->machine_name }}
                                    </option>
                                @endforeach
                            </select>

                            @error('site_machine')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- FAILURE DATE --}}
                        <div>
                            <label class="block mb-1 text-sm font-semibold text-gray-700">
                                Failure Date
                            </label>
                            <input type="date" name="failure_date"
                                value="{{ old('failure_date', now()->format('Y-m-d')) }}"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-200 focus:outline-none
            @error('failure_date') border-red-500 @enderror"
                                required>
                            @error('failure_date')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">

                        {{-- FAILED SUB-SYSTEM --}}
                        <div>
                            <label class="block mb-1 text-sm font-semibold text-gray-700">
                                Failed Sub-System
                            </label>
                            @php
                                $defaultSubsystem = isset($selectedStock) ? $selectedStock->sparepart->type ?? '' : '';
                            @endphp
                            <textarea name="failed_subsystem" rows="3"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-200
            @error('failed_subsystem') border-red-500 @enderror"
                                placeholder="Example: CCR Subsystem, Power Pack, etc.">{{ old('failed_subsystem', $defaultSubsystem) }}</textarea>

                            @error('failed_subsystem')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- FAILURE PHENOMENON --}}
                        <div>
                            <label class="block mb-1 text-sm font-semibold text-gray-700">
                                Failure Phenomenon
                            </label>
                            @php
                                $defaultPhenomenon = isset($selectedStock)
                                    ? 'Kerusakan komponen pada: ' .
                                        $selectedStock->sparepart->item_name .
                                        ' (SN: ' .
                                        ($selectedStock->sparepart->serial_number ?? '-') .
                                        ')'
                                    : '';
                            @endphp
                            <textarea name="failure_phenomenon" rows="3"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-200
            @error('failure_phenomenon') border-red-500 @enderror"
                                placeholder="Explain failure details">{{ old('failure_phenomenon', $defaultPhenomenon) }}</textarea>

                            @error('failure_phenomenon')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>

                    {{-- TS PROCEDURE --}}
                    <div>
                        <label class="block mb-1 text-sm font-semibold text-gray-700">
                            Troubleshoot Procedure
                        </label>
                        @php
                            $defaultTs = isset($selectedStock) ? $selectedStock->sparepart->note ?? '' : '';
                        @endphp
                        <textarea name="ts_procedure" rows="3" placeholder="Langkah perbaikan atau penggantian sparepart..."
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-200 focus:outline-none
                        @error('ts_procedure') border-red-500 @enderror">{{ old('ts_procedure', $defaultTs) }}</textarea>
                        @error('ts_procedure')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- IMAGE --}}
                    <div>
                        <label class="block mb-1 text-sm font-semibold text-gray-700">
                            Image Eviden
                        </label>
                        <input type="file" name="image" accept="image/png,image/jpeg,image/webp"
                            class="block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-blue-600 file:text-white hover:file:bg-blue-700 ">
                        @error('image')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- ACTION --}}
                    <div class="flex justify-end gap-4 p-6 border-t">
                        <a href="{{ route('report.index') }}"
                            class="px-5 py-2 text-sm font-semibold text-gray-700 transition bg-gray-200 rounded-lg hover:bg-gray-300">
                            Back
                        </a>
                        <button type="submit"
                            class="px-6 py-2 text-sm font-semibold text-white transition bg-green-600 rounded-lg hover:bg-green-700">
                            Save Report
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
@endsection
