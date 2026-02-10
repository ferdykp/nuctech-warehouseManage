@extends('layout.master')

@section('content')
    <div class="w-full py-3">
        <div class="max-w-6xl mx-auto overflow-hidden bg-white shadow-xl rounded-2xl">

            {{-- HEADER --}}
            <div class="px-6 py-5 border-b bg-gray-50">
                <h4 class="flex items-center gap-2 text-xl font-semibold text-gray-800">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Edit Sparepart {{ strtoupper($site) }}
                </h4>
                <div class="w-32 mt-2 border-b-4 border-blue-600"></div>
            </div>

            {{-- BODY --}}
            <div class="p-6">
                @php
                    $stock = $data->stocks->first();
                @endphp

                <form action="/{{ $site }}/{{ $data->id }}" method="POST" enctype="multipart/form-data"
                    class="space-y-8">
                    @csrf
                    @method('PUT')

                    {{-- BASIC INFORMATION --}}
                    <div>
                        <h5 class="mb-4 text-sm font-semibold text-gray-700 uppercase">
                            Basic Information
                        </h5>

                        <div class="grid gap-5 md:grid-cols-2">

                            {{-- ITEM NAME --}}
                            <div>
                                <label class="block mb-1 text-sm font-medium">Item Name</label>
                                <input type="text" name="item_name" value="{{ old('item_name', $data->item_name) }}"
                                    class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-blue-200 focus:outline-none
                                @error('item_name') border-red-500 @enderror"
                                    required>
                                @error('item_name')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- TYPE --}}
                            <div>
                                <label class="block mb-1 text-sm font-medium">Type</label>
                                <input type="text" name="type" value="{{ old('type', $data->type) }}"
                                    class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-blue-200 focus:outline-none
                                @error('type') border-red-500 @enderror"
                                    required>
                                @error('type')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- STOCK | UOM | DATE --}}
                            <div class="grid gap-5 md:grid-cols-3 md:col-span-2">

                                {{-- STOCK --}}
                                {{-- <div>
                                    <label class="block mb-1 text-sm font-medium">Stock</label>
                                    <input type="number" name="qty" value="{{ old('qty', $data->qty) }}"
                                        class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-blue-200 focus:outline-none
                                    @error('qty') border-red-500 @enderror"
                                        required>
                                    @error('qty')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div> --}}
                                <div>
                                    <label class="block mb-1 text-sm font-medium">Stock</label>
                                    <input type="number" name="qty" value="{{ old('qty', $stock?->qty) }}"
                                        class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-blue-200 focus:outline-none
                                    @error('qty') border-red-500 @enderror"
                                        required>
                                    @error('qty')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- UOM --}}
                                <div>
                                    <label class="block mb-1 text-sm font-medium text-left">UOM</label>
                                    <select name="uom"
                                        class="w-full px-4 py-2 text-center bg-white border rounded-lg
                                    focus:ring focus:ring-blue-200 focus:outline-none
                                    @error('uom') border-red-500 @enderror"
                                        required>
                                        <option value="" disabled>-- Pilih Satuan --</option>
                                        <option value="pcs" {{ old('uom', $data->uom) == 'pcs' ? 'selected' : '' }}>
                                            Pieces</option>
                                        <option value="set" {{ old('uom', $data->uom) == 'set' ? 'selected' : '' }}>
                                            Set</option>
                                        <option value="meter" {{ old('uom', $data->uom) == 'meter' ? 'selected' : '' }}>
                                            Meter</option>
                                        <option value="liter" {{ old('uom', $data->uom) == 'liter' ? 'selected' : '' }}>
                                            Liter</option>
                                        <option value="pack" {{ old('uom', $data->uom) == 'pack' ? 'selected' : '' }}>
                                            Pack</option>
                                    </select>
                                    @error('uom')
                                        <p class="mt-1 text-xs text-center text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block mb-1 text-sm font-medium">Condition</label>

                                    <select name="condition"
                                        class="w-full px-4 py-2 border rounded-lg bg-white focus:ring focus:ring-blue-200 focus:outline-none
                                        @error('condition') border-red-500 @enderror"
                                        required>

                                        <option value="" disabled>-- Pilih Kondisi --</option>

                                        @php
                                            $conditions = [
                                                'new' => 'New',
                                                'used-good' => 'Used-Good',
                                                'damaged' => 'Damaged',
                                                'repair' => 'Repair',
                                            ];
                                        @endphp

                                        {{-- @foreach ($conditions as $value => $label)
                                            <option value="{{ $value }}"
                                                {{ old('condition', $data->condition) == $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach --}}
                                        @foreach ($conditions as $value => $label)
                                            <option value="{{ $value }}"
                                                {{ old('condition', $stock?->condition) == $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach

                                    </select>

                                    @error('condition')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>





                        </div>
                    </div>

                    {{-- ADDITIONAL INFORMATION --}}
                    <div>
                        <h5 class="mb-4 text-sm font-semibold text-gray-700 uppercase">
                            Additional Information
                        </h5>

                        {{-- NOTE --}}
                        <div class="mb-5">
                            <label class="block mb-1 text-sm font-medium">Note</label>
                            <textarea name="note" rows="3"
                                class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-blue-200 focus:outline-none
                            @error('note') border-red-500 @enderror">{{ old('note', $data->note) }}</textarea>
                            @error('note')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- IMAGE --}}
                        <div>
                            <label class="block mb-1 text-sm font-medium">Image</label>

                            @if ($data->image)
                                <img src="{{ asset('storage/' . $data->image) }}"
                                    class="object-cover w-24 h-24 mb-3 rounded-lg shadow">
                            @endif

                            <input type="file" name="image"
                                class="block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-blue-600 file:text-white hover:file:bg-blue-700">
                            @error('image')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- ACTION --}}
                    <div class="flex justify-between pt-6 border-t">
                        <a href="{{ route('sparepart.index', ['site' => 'ebeam']) }}"
                            class="px-4 py-2 text-sm font-semibold text-gray-600 bg-gray-200 rounded-lg hover:bg-gray-300">
                            ← Back
                        </a>
                        <button type="submit"
                            class="px-6 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                            Update Data
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
    {{-- {{ dd($data) }} --}}
@endsection
