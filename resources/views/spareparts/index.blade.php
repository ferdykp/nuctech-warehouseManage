@extends('layout.master')

@section('content')
    <div class="w-full px-6 py-6">
        <div class="bg-white shadow rounded-2xl">

            {{-- HEADER DINAMIS --}}
            <div class="px-6 py-4 border-b">
                <h2 class="text-xl font-semibold uppercase">
                    {{ $siteData->machine_name }}
                </h2>
                <p class="text-sm text-gray-500">{{ $siteData->branch->branch_name }} • Lokasi Inventory</p>
                <div class="w-32 mt-2 border-b-4 border-red-600"></div>
            </div>

            {{-- ACTION & FILTERS --}}
            <div class="p-6">
                <div class="flex flex-col gap-3 mb-4 md:flex-row md:items-center md:justify-between">
                    <div class="flex flex-wrap gap-2">
                        @if (Auth::user()->role === 'admin')
                            <button onclick="openCreateModal()"
                                class="flex items-center gap-2 p-3 text-sm font-semibold text-white transition-all bg-green-600 rounded-lg hover:bg-green-700">
                                <i class="fa-solid fa-plus"></i> Tambah Sparepart
                            </button>

                            <button onclick="document.getElementById('modal-import').classList.remove('hidden')"
                                class="flex items-center gap-2 p-3 text-sm font-semibold text-white transition-all bg-blue-600 rounded-lg hover:bg-blue-700">
                                <i class="fa-solid fa-file-import"></i> Import Excel
                            </button>

                            <a href="{{ route('sparepart.export', $slug) }}"
                                class="p-3 text-sm font-semibold text-white rounded-lg bg-emerald-600 hover:bg-emerald-700">
                                <i class="fa-solid fa-file-excel"></i> Export Excel
                            </a>
                        @endif
                    </div>

                    <div class="flex gap-2 w-full md:w-auto">
                        {{-- FILTER KONDISI --}}
                        <select id="filter-condition" class="px-4 py-2 text-sm border rounded-lg focus:ring focus:ring-blue-200 outline-none">
                            <option value="">Semua Kondisi</option>
                            <option value="new">New</option>
                            <option value="used-good">Used Good</option>
                            <option value="damaged">Damaged</option>
                            <option value="repair">Repair</option>
                        </select>

                        {{-- SEARCH BAR --}}
                        <div class="w-full md:w-72">
                            <input type="text" id="search" name="search"
                                data-route="{{ route('sparepart.index', $slug) }}" placeholder="Cari Nama atau SN..."
                                autocomplete="off"
                                class="w-full px-4 py-2 text-sm border rounded-lg focus:ring focus:ring-blue-200 focus:outline-none">
                        </div>
                    </div>
                </div>
            </div>

            <div id="table-container">
                @include('spareparts.table', [
                    'assets' => $data,
                    'all_sites' => $all_sites,
                ])
            </div>
        </div>
    </div>

    {{-- MODAL CREATE --}}
    <div id="modal-create" class="fixed inset-0 z-50 flex items-center justify-center hidden px-4 bg-black/60 backdrop-blur-sm">
        <div class="relative w-full max-w-2xl overflow-hidden bg-white shadow-2xl rounded-2xl">
            <div class="flex items-center justify-between px-6 py-4 border-b bg-gray-50">
                <h3 class="text-xl font-bold text-gray-800">Tambah Sparepart Baru</h3>
                <button onclick="closeCreateModal()" class="text-gray-400 transition-colors hover:text-red-500">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>
            <form action="{{ route('sparepart.store', $slug) }}" method="POST" enctype="multipart/form-data" class="p-6">
                @csrf
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="block mb-1 text-sm font-semibold text-gray-700">Item Name</label>
                        <input type="text" name="item_name" required placeholder="Contoh: Roller Conveyor" class="w-full p-2.5 border rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                    </div>
                    <div>
                        <label class="block mb-1 text-sm font-semibold text-gray-700">Serial Number</label>
                        <input type="text" name="serial_number" required placeholder="NUC1234" class="w-full p-2.5 border rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                    </div>
                    <div>
                        <label class="block mb-1 text-sm font-semibold text-gray-700">Type / Model</label>
                        <input type="text" name="type" required placeholder="Contoh: FS6000-X1" class="w-full p-2.5 border rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                    </div>
                    <div>
                        <label class="block mb-1 text-sm font-semibold text-gray-700">Satuan (UOM)</label>
                        <select name="uom" required class="w-full p-2.5 border rounded-lg outline-none">
                            <option value="PCS">PCS</option>
                            <option value="SET">SET</option>
                            <option value="UNIT">UNIT</option>
                        </select>
                    </div>
                    <div>
                        <label class="block mb-1 text-sm font-semibold text-gray-700">Jumlah Stok Awal</label>
                        <input type="number" name="qty" required min="1" value="1" class="w-full p-2.5 border rounded-lg outline-none">
                    </div>
                    <div>
                        <label class="block mb-1 text-sm font-semibold text-gray-700">Kondisi</label>
                        <select name="condition" required class="w-full p-2.5 border rounded-lg outline-none">
                            <option value="new">NEW (Baru)</option>
                            <option value="used-good">USED (Bagus)</option>
                            <option value="damaged">DAMAGED (Rusak)</option>
                            <option value="repair">REPAIRED (Hasil Perbaikan)</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-8">
                    <button type="button" onclick="closeCreateModal()" class="px-6 py-2.5 text-sm font-semibold text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200">Batal</button>
                    <button type="submit" class="px-6 py-2.5 text-sm font-bold text-white bg-green-600 rounded-lg hover:bg-green-700">Simpan Sparepart</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL IMPORT EXCEL --}}
    <div id="modal-import" class="fixed inset-0 z-50 flex items-center justify-center hidden bg-black/50 backdrop-blur-sm">
        <div class="bg-white p-6 rounded-2xl w-full max-w-md shadow-2xl">
            <h3 class="text-xl font-bold mb-4 text-gray-800">Import Sparepart via Excel</h3>
            <form action="{{ route('sparepart.import', $slug) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-4">
                    <label class="block mb-2 text-sm text-gray-600 font-medium">Pilih file .xlsx / .xls / .csv</label>
                    <input type="file" name="file" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" required>
                </div>
                <div class="flex justify-end gap-2 mt-8">
                    <button type="button" onclick="document.getElementById('modal-import').classList.add('hidden')" class="px-5 py-2 text-sm font-semibold text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200">Batal</button>
                    <button type="submit" class="px-5 py-2 text-sm font-bold text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-lg shadow-blue-100">Upload & Import</button>
                </div>
            </form>
        </div>
    </div>
@endsection