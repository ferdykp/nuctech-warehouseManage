@extends('layout.master')

@section('content')
    <div class="w-full px-6 py-3">
        <div class="overflow-hidden bg-white shadow-2xl rounded-2xl">

            {{-- HEADER --}}
            <div class="px-8 py-6 border-b bg-gray-50">
                <h4 class="text-2xl font-bold text-gray-800">
                    Tambah Sparepart {{ $siteData->name }}
                </h4>
                <p class="mt-1 text-sm text-gray-500">
                    Form input data sparepart untuk lokasi {{ $siteData->name }}
                </p>
                <div class="w-24 mt-3 border-b-4 border-red-600 rounded"></div>
            </div>

            {{-- BODY --}}
            <div class="px-8">
                <form class="space-y-6" action="/{{ $site }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    {{-- ITEM NAME --}}
                    <div>
                        <label class="block mb-1 text-sm font-semibold text-gray-700">
                            Item Name
                        </label>
                        <input type="text" name="item_name" value="{{ old('item_name') }}"
                            placeholder="Contoh: Power Supply FS6000"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-200 focus:outline-none
                        @error('item_name') border-red-500 @enderror"
                            required>
                        @error('item_name')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- TYPE --}}
                    <div>
                        <label class="block mb-1 text-sm font-semibold text-gray-700">
                            Type
                        </label>
                        <input type="text" name="type" value="{{ old('type') }}"
                            placeholder="Contoh: Module / Board"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-200 focus:outline-none
                        @error('type') border-red-500 @enderror"
                            required>
                        @error('type')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- GRID --}}
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">

                        {{-- STOCK --}}
                        <div>
                            <label class="block mb-1 text-sm font-semibold text-gray-700">
                                Stock
                            </label>
                            <input type="number" name="qty" value="{{ old('qty') }}" placeholder="0"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-200 focus:outline-none
            @error('qty') border-red-500 @enderror"
                                required>
                            @error('qty')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- UOM --}}
                        <div>
                            <label class="block mb-1 text-sm font-semibold text-gray-700">
                                UOM
                            </label>
                            <select name="uom"
                                class="w-full px-4 py-2 border rounded-lg bg-white focus:ring-2 focus:ring-blue-200 focus:outline-none
            @error('uom') border-red-500 @enderror"
                                required>
                                <option value="" disabled {{ old('uom') ? '' : 'selected' }}>
                                    -- Pilih Satuan --
                                </option>
                                <option value="pcs" {{ old('uom') == 'pcs' ? 'selected' : '' }}>Pieces</option>
                                <option value="set" {{ old('uom') == 'set' ? 'selected' : '' }}>Set</option>
                                <option value="meter" {{ old('uom') == 'meter' ? 'selected' : '' }}>Meter</option>
                                <option value="liter" {{ old('uom') == 'liter' ? 'selected' : '' }}>Liter</option>
                                <option value="pack" {{ old('uom') == 'pack' ? 'selected' : '' }}>Pack</option>
                            </select>
                            @error('uom')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block mb-1 text-sm font-semibold text-gray-700">
                                Condition
                            </label>
                            <select name="condition"
                                class="w-full px-4 py-2 border rounded-lg bg-white focus:ring-2 focus:ring-blue-200 focus:outline-none
                                    @error('condition') border-red-500 @enderror"
                                required>
                                <option value="" disabled {{ old('condition') ? '' : 'selected' }}>
                                    -- Pilih Kondisi --
                                </option>
                                <option value="new" {{ old('condition') == 'new' ? 'selected' : '' }}>New</option>
                                <option value="used-good" {{ old('condition') == 'used-good' ? 'selected' : '' }}>Used-Good
                                </option>
                                <option value="damaged" {{ old('condition') == 'damaged' ? 'selected' : '' }}>Damaged
                                </option>
                                <option value="repair" {{ old('condition') == 'repair' ? 'selected' : '' }}>Repair</option>
                            </select>
                            @error('condition')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>


                    {{-- NOTE --}}
                    <div>
                        <label class="block mb-1 text-sm font-semibold text-gray-700">
                            Note
                        </label>
                        <textarea name="note" rows="3" placeholder="Catatan tambahan..."
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-200 focus:outline-none
                        @error('note') border-red-500 @enderror">{{ old('note') }}</textarea>
                        @error('note')
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
                        <a href="{{ route('sparepart.index', ['site' => 'ebeam']) }}"
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
