@extends('layout.master')

@section('content')
    <div class="w-full px-6 py-3">
        <div class="overflow-hidden bg-white shadow-2xl rounded-2xl">

            {{-- HEADER --}}
            <div class="px-8 py-6 border-b bg-gray-50">
                <h4 class="text-2xl font-bold text-gray-800">
                    Add Report
                </h4>
                <p class="mt-1 text-sm text-gray-500">
                    Form input data report
                </p>
                <div class="w-24 mt-3 border-b-4 border-red-600 rounded"></div>
            </div>

            {{-- BODY --}}
            <div class="px-8">
                <form action="{{ route('report.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                    @csrf

                    {{-- ITEM NAME --}}
                    <div>
                        <label class="block mb-1 text-sm font-semibold text-gray-700">
                            Attendant
                        </label>
                        <input type="text" name="attendant" value="{{ old('attendant') }}"
                            placeholder="Contoh: Power Supply FS6000"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-200 focus:outline-none
                        @error('attendant') border-red-500 @enderror"
                            required>
                        @error('attendant')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">

                        {{-- TYPE --}}
                        <div>
                            <label class="block mb-1 text-sm font-semibold text-gray-700">
                                Site Machine
                            </label>
                            <select name="site_machine" id="site_machine"
                                class="w-full px-4 py-2 border rounded-lg bg-white focus:ring-2 focus:ring-blue-200 focus:outline-none
    @error('site_machine') border-red-500 @enderror"
                                required>

                                <option value="" disabled selected>-- Pilih Site --</option>
                                <option value="fsjkt">FS6000 Jakarta</option>
                                <option value="fssmg">FS6000 Semarang</option>
                                <option value="fssby">FS6000 Surabaya</option>
                                <option value="ebeam">E-Beam</option>
                            </select>

                            @error('site_machine')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block mb-1 text-sm font-semibold text-gray-700">
                                Series Machine
                            </label>
                            <select name="series_machine" id="series_machine"
                                class="w-full px-4 py-2 border rounded-lg bg-white focus:ring-2 focus:ring-blue-200 focus:outline-none
    @error('series_machine') border-red-500 @enderror"
                                required>

                                <option value="" disabled selected>-- Pilih Series --</option>

                                <!-- FS6000 -->
                                <option value="export" data-site="fssby fsjkt fssmg">
                                    TFN DU-11892 (Export)
                                </option>
                                <option value="import" data-site="fssby fsjkt fssmg">
                                    TFN DU-11891 (Import)
                                </option>

                                <!-- E-Beam -->
                                <option value="ebeam_machine" data-site="ebeam">
                                    IS1020
                                </option>
                            </select>

                            @error('series_machine')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block mb-1 text-sm font-semibold text-gray-700">
                                Failure Date
                            </label>
                            <input type="date" name="failure_date" value="{{ old('failure_date') }}"
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
                            <textarea name="failed_subsystem" rows="3"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-200
            @error('failed_subsystem') border-red-500 @enderror"
                                placeholder="Example: CCR Subsystem">{{ old('failed_subsystem') }}</textarea>

                            @error('failed_subsystem')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- FAILURE PHENOMENON --}}
                        <div>
                            <label class="block mb-1 text-sm font-semibold text-gray-700">
                                Failure Phenomenon
                            </label>
                            <textarea name="failure_phenomenon" rows="3"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-200
            @error('failure_phenomenon') border-red-500 @enderror"
                                placeholder="Explain failure">{{ old('failure_phenomenon') }}</textarea>

                            @error('failure_phenomenon')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>


                    <div>
                        <label class="block mb-1 text-sm font-semibold text-gray-700">
                            Troubleshoot Procedure
                        </label>
                        <textarea name="ts_procedure" rows="3" placeholder="Catatan tambahan..."
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-200 focus:outline-none
                        @error('ts_procedure') border-red-500 @enderror">{{ old('ts_procedure') }}</textarea>
                        @error('ts_procedure')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- IMAGE --}}
                    <div>
                        <label class="block mb-1 text-sm font-semibold text-gray-700">
                            Image
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
                            Save
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const siteSelect = document.getElementById('site_machine');
            const seriesSelect = document.getElementById('series_machine');
            const seriesOptions = Array.from(seriesSelect.options);

            siteSelect.addEventListener('change', function() {
                const selectedSite = this.value;

                seriesSelect.value = '';

                seriesOptions.forEach(option => {
                    if (!option.dataset.site) return;

                    const allowedSites = option.dataset.site.split(' ');
                    option.hidden = !allowedSites.includes(selectedSite);
                });
            });
        });
    </script>
@endpush
