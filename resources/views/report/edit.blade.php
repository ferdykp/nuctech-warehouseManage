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
                    Update data report
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

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">

                        {{-- SITE MACHINE --}}
                        <div>
                            <label class="block mb-1 text-sm font-semibold text-gray-700">
                                Site Machine
                            </label>
                            <select name="site_machine" id="site_machine"
                                class="w-full px-4 py-2 border rounded-lg bg-white
                            @error('site_machine') border-red-500 @enderror"
                                required>

                                <option value="fsjkt"
                                    {{ old('site_machine', $report->site_machine) == 'fsjkt' ? 'selected' : '' }}>FS6000
                                    Jakarta
                                </option>
                                <option value="fssmg"
                                    {{ old('site_machine', $report->site_machine) == 'fssmg' ? 'selected' : '' }}>FS6000
                                    Semarang
                                </option>
                                <option value="fssby"
                                    {{ old('site_machine', $report->site_machine) == 'fssby' ? 'selected' : '' }}>FS6000
                                    Surabaya
                                </option>
                                <option value="report"
                                    {{ old('site_machine', $report->site_machine) == 'report' ? 'selected' : '' }}>E-Beam
                                </option>
                            </select>
                        </div>

                        {{-- SERIES MACHINE --}}
                        <div>
                            <label class="block mb-1 text-sm font-semibold text-gray-700">
                                Series Machine
                            </label>
                            <select name="series_machine" id="series_machine"
                                class="w-full px-4 py-2 bg-white border rounded-lg" required>

                                <option value="export" data-site="fssby fsjkt fssmg"
                                    {{ old('series_machine', $report->series_machine) == 'export' ? 'selected' : '' }}>
                                    TFN DU-11892 (Export)
                                </option>
                                <option value="import" data-site="fssby fsjkt fssmg"
                                    {{ old('series_machine', $report->series_machine) == 'import' ? 'selected' : '' }}>
                                    TFN DU-11891
                                </option>
                                <option value="report_machine" data-site="report"
                                    {{ old('series_machine', $report->series_machine) == 'report_machine' ? 'selected' : '' }}>
                                    IS1020
                                </option>
                            </select>
                        </div>

                        {{-- FAILURE DATE --}}
                        <div>
                            <label class="block mb-1 text-sm font-semibold text-gray-700">
                                Failure Date
                            </label>
                            <input type="date" name="failure_date"
                                value="{{ old('failure_date', $report->failure_date) }}"
                                class="w-full px-4 py-2 border rounded-lg" required>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">

                        {{-- FAILED SUB SYSTEM --}}
                        <div>
                            <label class="block mb-1 text-sm font-semibold text-gray-700">
                                Failed Sub-System
                            </label>
                            <textarea name="failed_subsystem" rows="3" class="w-full px-4 py-2 border rounded-lg">{{ old('failed_subsystem', $report->failed_subsystem) }}</textarea>
                        </div>

                        {{-- FAILURE PHENOMENON --}}
                        <div>
                            <label class="block mb-1 text-sm font-semibold text-gray-700">
                                Failure Phenomenon
                            </label>
                            <textarea name="failure_phenomenon" rows="3" class="w-full px-4 py-2 border rounded-lg">{{ old('failure_phenomenon', $report->failure_phenomenon) }}</textarea>
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
                            Image
                        </label>

                        @if ($report->image)
                            <img src="{{ asset('storage/' . $report->image) }}"
                                class="object-cover w-32 h-32 mb-3 border rounded-xl">
                        @endif

                        <input type="file" name="image" class="block w-full text-sm text-gray-600">
                    </div>

                    {{-- ACTION --}}
                    <div class="flex justify-end gap-4 p-6 border-t">
                        <a href="{{ route('report.index') }}"
                            class="px-5 py-2 text-sm font-semibold bg-gray-200 rounded-lg">
                            Back
                        </a>
                        <button type="submit" class="px-6 py-2 text-sm font-semibold text-white bg-green-600 rounded-lg">
                            Update
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
@endsection
