@extends('layout.master')

@section('content')
    <div class="w-full px-6 py-3">
        <div class="overflow-hidden bg-white shadow-2xl rounded-2xl">

            {{-- HEADER --}}
            <div class="px-8 py-6 border-b bg-gray-50">
                <h4 class="text-2xl font-bold text-gray-800">
                    Edit Report
                </h4>
                <p class="mt-1 text-sm text-gray-500">
                    Update data log report kerusakan alat/komponen.
                </p>
                <div class="w-24 mt-3 border-b-4 border-red-600 rounded"></div>
            </div>

            {{-- BODY --}}
            <div class="px-8">
                <form action="{{ route('report.update', $report->id) }}" method="POST" enctype="multipart/form-data"
                    class="space-y-6">
                    @csrf
                    @method('PUT')

                    {{-- ATTENDANT --}}
                    <div>
                        <label class="block mb-1 text-sm font-semibold text-gray-700">
                            Attendant
                        </label>
                        <input type="text" name="attendant" value="{{ old('attendant', $report->attendant) }}"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-200
                        @error('attendant') border-red-500 @enderror"
                            required>
                    </div>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

                        {{-- SITE MACHINE --}}
                        <div>
                            <label class="block mb-1 text-sm font-semibold text-gray-700">
                                Site Machine
                            </label>
                            <select name="site_machine" id="site_machine"
                                class="w-full px-4 py-2 border rounded-lg bg-white
                            @error('site_machine') border-red-500 @enderror"
                                required>
                                <option value="" disabled>-- Pilih Site --</option>
                                @foreach ($sites as $site)
                                    <option value="{{ $site->slug }}"
                                        {{ old('site_machine', $report->site_machine) == $site->slug ? 'selected' : '' }}>
                                        {{ $site->machine_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- FAILURE DATE --}}
                        <div>
                            <label class="block mb-1 text-sm font-semibold text-gray-700">
                                Failure Date
                            </label>
                            <input type="date" name="failure_date"
                                value="{{ old('failure_date', $report->failure_date ? \Carbon\Carbon::parse($report->failure_date)->format('Y-m-d') : '') }}"
                                class="w-full px-4 py-2 border rounded-lg" required>
                        </div>
                    </div>

                    {{-- Ekstraksi data note jika kolom input aslinya digabung pada DB --}}
                    @php
                        $subsystem = $report->failed_subsystem;
                        $phenomenon = $report->failure_phenomenon;

                        if (empty($subsystem) && !empty($report->failure_note)) {
                            preg_match(
                                '/Failed Sub-System:\n(.*?)\n\nFailure Phenomenon:\n(.*)/s',
                                $report->failure_note,
                                $matches,
                            );
                            $subsystem = $matches[1] ?? '';
                            $phenomenon = $matches[2] ?? $report->failure_note;
                        }
                    @endphp

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">

                        {{-- FAILED SUB SYSTEM --}}
                        <div>
                            <label class="block mb-1 text-sm font-semibold text-gray-700">
                                Failed Sub-System
                            </label>
                            <textarea name="failed_subsystem" rows="3" class="w-full px-4 py-2 border rounded-lg">{{ old('failed_subsystem', $subsystem) }}</textarea>
                        </div>

                        {{-- FAILURE PHENOMENON --}}
                        <div>
                            <label class="block mb-1 text-sm font-semibold text-gray-700">
                                Failure Phenomenon
                            </label>
                            <textarea name="failure_phenomenon" rows="3" class="w-full px-4 py-2 border rounded-lg">{{ old('failure_phenomenon', $phenomenon) }}</textarea>
                        </div>

                    </div>

                    {{-- TS PROCEDURE --}}
                    <div>
                        <label class="block mb-1 text-sm font-semibold text-gray-700">
                            Troubleshoot Procedure
                        </label>
                        <textarea name="ts_procedure" rows="3" class="w-full px-4 py-2 border rounded-lg">{{ old('ts_procedure', $report->ts_procedure) }}</textarea>
                    </div>

                    {{-- IMAGE --}}
                    <div>
                        <label class="block mb-1 text-sm font-semibold text-gray-700">
                            Image Eviden
                        </label>

                        @if ($report->image)
                            <img src="{{ asset('storage/' . $report->image) }}"
                                class="object-cover w-32 h-32 mb-3 border shadow-sm rounded-xl">
                        @endif

                        <input type="file" name="image"
                            class="block w-full text-sm text-gray-500 text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-gray-100 hover:file:bg-gray-200">
                    </div>

                    {{-- ACTION --}}
                    <div class="flex justify-end gap-4 p-6 border-t">
                        <a href="{{ route('report.index') }}"
                            class="px-5 py-2 text-sm font-semibold text-gray-700 transition bg-gray-200 rounded-lg hover:bg-gray-300">
                            Back
                        </a>
                        <button type="submit"
                            class="px-6 py-2 text-sm font-semibold text-white transition bg-green-600 rounded-lg hover:bg-green-700">
                            Update Report
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
@endsection
