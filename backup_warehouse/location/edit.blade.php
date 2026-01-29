@extends('layout.master')

@section('content')
    <div class="w-full px-6 py-3">
        <div class="overflow-hidden bg-white shadow-2xl rounded-2xl">

            {{-- HEADER --}}
            <div class="px-8 py-6 border-b bg-gray-50">
                <h4 class="text-2xl font-bold text-gray-800">
                    Edit Lokasi Penyimpanan
                </h4>
                <p class="mt-1 text-sm text-gray-500">
                    Edit data lokasi sparepart
                </p>
                <div class="w-24 mt-3 border-b-4 border-red-600 rounded"></div>
            </div>

            {{-- BODY --}}
            <div class="px-8">
                <form action="{{ route('location.update', $location->id) }}" method="POST" enctype="multipart/form-data"
                    class="space-y-6">
                    @csrf
                    @method('PUT')

                    {{-- MACHINE TYPE --}}
                    <div>
                        <label class="block mb-1 text-sm font-semibold text-gray-700">
                            Machine Type
                        </label>

                        <select name="machine_type"
                            class="w-full px-4 py-2 border rounded-lg bg-white focus:ring-2 focus:ring-blue-200 focus:outline-none
                        @error('machine_type') border-red-500 @enderror"
                            required>
                            <option value="" disabled>-- Pilih Machine Type --</option>

                            <option value="fs6000jkt"
                                {{ old('machine_type', $location->machine_type) == 'fs6000jkt' ? 'selected' : '' }}>
                                FS6000 Jakarta
                            </option>
                            <option value="fs6000sby"
                                {{ old('machine_type', $location->machine_type) == 'fs6000sby' ? 'selected' : '' }}>
                                FS6000 Surabaya
                            </option>
                            <option value="location"
                                {{ old('machine_type', $location->machine_type) == 'location' ? 'selected' : '' }}>
                                FS6000 Semarang
                            </option>
                            <option value="ebeam"
                                {{ old('machine_type', $location->machine_type) == 'ebeam' ? 'selected' : '' }}>
                                E-Beam
                            </option>
                            <option value="ctmic"
                                {{ old('machine_type', $location->machine_type) == 'ctmic' ? 'selected' : '' }}>
                                CTMIC
                            </option>
                        </select>

                        @error('machine_type')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- LOCATION NAME --}}
                    <div>
                        <label class="block mb-1 text-sm font-semibold text-gray-700">
                            Location
                        </label>
                        <input type="text" name="location_name"
                            value="{{ old('location_name', $location->location_name) }}"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-200 focus:outline-none
                        @error('location_name') border-red-500 @enderror"
                            required>

                        @error('location_name')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- ACTION --}}
                    <div class="flex justify-end gap-4 p-6 border-t">
                        <a href="{{ route('location.index') }}"
                            class="px-5 py-2 text-sm font-semibold text-gray-700 transition bg-gray-200 rounded-lg hover:bg-gray-300">
                            Back
                        </a>

                        <button type="submit"
                            class="px-6 py-2 text-sm font-semibold text-white transition bg-green-600 rounded-lg hover:bg-green-700">
                            Update
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
@endsection
