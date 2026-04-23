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

            {{-- NOTIFICATION PANEL UNTUK APPROVAL & RECEIVE --}}
            @php
                $pendingApprovals = \App\Models\SparepartTransfer::where('from_site_id', $siteData->id)
                    ->where('status', 'pending')
                    ->get();
                $pendingReceipts = \App\Models\SparepartTransfer::where('to_site_id', $siteData->id)
                    ->where('status', 'approved')
                    ->get();
            @endphp

            @if ($pendingApprovals->count() > 0 || $pendingReceipts->count() > 0)
                <div class="p-6 space-y-4">
                    @foreach ($pendingApprovals as $t)
                        <div
                            class="flex items-center justify-between p-4 border-l-4 border-yellow-500 bg-yellow-50 rounded-r-xl">
                            <div>
                                <p class="text-sm font-bold text-yellow-800">REQUEST MASUK: {{ $t->sparepart->item_name }}
                                    ({{ $t->qty }} pcs)
                                </p>
                                <p class="text-xs text-yellow-600">Diminta oleh: {{ $t->toSite->machine_name }} • Kondisi:
                                    {{ $t->condition }}</p>
                            </div>
                            <form action="{{ route('movement.approve', $t->id) }}" method="POST">
                                @csrf
                                <button type="submit"
                                    class="px-4 py-2 text-xs font-bold text-white bg-green-600 rounded-lg hover:bg-green-700">APPROVE
                                    & KIRIM</button>
                            </form>
                        </div>
                    @endforeach

                    @if (Auth::user()->role === 'superadmin' ||
                            (Auth::user()->role === 'admin_site' && Auth::user()->site_id === $siteData->id))
                        @foreach ($pendingReceipts as $t)
                            <div
                                class="flex items-center justify-between p-4 border-l-4 border-blue-500 bg-blue-50 rounded-r-xl">
                                <div>
                                    <p class="text-sm font-bold text-blue-800">BARANG DATANG: {{ $t->sparepart->item_name }}
                                        ({{ $t->qty }} pcs)
                                    </p>
                                    <p class="text-xs text-blue-600">Dikirim dari: {{ $t->fromSite->machine_name }} • Tunggu
                                        Konfirmasi Fisik</p>
                                </div>
                                <form action="{{ route('movement.receive', $t->id) }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                        class="px-4 py-2 text-xs font-bold text-white bg-blue-600 rounded-lg hover:bg-blue-700">KONFIRMASI
                                        TERIMA</button>
                                </form>
                            </div>
                        @endforeach
                    @endif
                </div>
            @endif

            {{-- ACTION --}}
            <div class="p-6">
                <div class="flex flex-col gap-3 mb-4 md:flex-row md:items-center md:justify-between">
                    <div class="flex flex-wrap gap-2">
                        {{-- 
        Cek: 
        1. Apakah user adalah superadmin? 
        ATAU 
        2. Apakah user adalah admin_site DAN site_id user sama dengan id site yang sedang dibuka?
    --}}
                        @if (Auth::user()->role === 'superadmin' ||
                                (Auth::user()->role === 'admin_site' && Auth::user()->site_id === $siteData->id))
                            <button onclick="openCreateModal()"
                                class="flex items-center gap-2 p-3 text-sm font-semibold text-white transition-all bg-green-600 rounded-lg shadow-md hover:bg-green-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                                        clip-rule="evenodd" />
                                </svg>
                                Tambah Sparepart
                            </button>

                            <a href="{{ route('sparepart.export', $slug) }}"
                                class="p-3 text-sm font-semibold text-white rounded-lg shadow-md bg-emerald-600 hover:bg-emerald-700">
                                Export Excel
                            </a>
                        @endif
                    </div>

                    <div class="w-full md:w-72">
                        <input type="text" id="search" name="search"
                            data-route="{{ route('sparepart.search', $slug) }}" placeholder="Search item..."
                            autocomplete="off"
                            class="w-full px-4 py-2 text-sm border rounded-lg focus:ring focus:ring-blue-200 focus:outline-none">
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
@endsection

<div id="modal-create"
    class="fixed inset-0 z-50 flex items-center justify-center hidden px-4 bg-black/60 backdrop-blur-sm">
    <div class="relative w-full max-w-2xl overflow-hidden bg-white shadow-2xl rounded-2xl">

        <div class="flex items-center justify-between px-6 py-4 border-b bg-gray-50">
            <h3 class="text-xl font-bold text-gray-800">Tambah Sparepart Baru</h3>
            <button onclick="closeCreateModal()" class="text-gray-400 transition-colors hover:text-red-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form action="{{ route('sparepart.store', $slug) }}" method="POST" enctype="multipart/form-data"
            class="p-6">
            @csrf
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                {{-- <div class="md:col-span-2">
                    <label class="block mb-1 text-sm font-semibold text-gray-700">Nama Item / Sparepart</label>
                    <input type="text" name="item_name" required placeholder="Contoh: Roller Conveyor"
                        class="w-full p-2.5 border rounded-lg focus:ring-2 focus:ring-green-500 outline-none transition-all">
                </div> --}}
                <div>
                    <label class="block mb-1 text-sm font-semibold text-gray-700">Item Name</label>
                    <input type="text" name="item_name" required placeholder="Contoh: Roller Conveyor"
                        class="w-full p-2.5 border rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                </div>

                <div>
                    <label class="block mb-1 text-sm font-semibold text-gray-700">Serial Number</label>
                    <input type="text" name="serial_number" required placeholder="NUC1234"
                        class="w-full p-2.5 border rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                </div>

                <div>
                    <label class="block mb-1 text-sm font-semibold text-gray-700">Type / Model</label>
                    <input type="text" name="type" required placeholder="Contoh: FS6000-X1"
                        class="w-full p-2.5 border rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                </div>

                <div>
                    <label class="block mb-1 text-sm font-semibold text-gray-700">Satuan (UOM)</label>
                    <select name="uom" required
                        class="w-full p-2.5 border rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                        <option value="PCS">PCS</option>
                        <option value="SET">SET</option>
                        <option value="UNIT">UNIT</option>
                        <option value="BOX">BOX</option>
                    </select>
                </div>

                <div>
                    <label class="block mb-1 text-sm font-semibold text-gray-700">Jumlah Stok Awal</label>
                    <input type="number" name="qty" required min="1" value="1"
                        class="w-full p-2.5 border rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                </div>

                <div>
                    <label class="block mb-1 text-sm font-semibold text-gray-700">Kondisi</label>
                    <select name="condition" required
                        class="w-full p-2.5 border rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                        <option value="new">NEW (Baru)</option>
                        <option value="used-good">USED (Bagus)</option>
                        <option value="damaged">DAMAGED (Rusak)</option>
                        <option value="repair">REPAIRED (Hasil Perbaikan)</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block mb-1 text-sm font-semibold text-gray-700">Foto Sparepart (Optional)</label>
                    <input type="file" name="image" accept="image/*"
                        class="w-full p-2 border border-gray-300 border-dashed rounded-lg file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                </div>

                <div class="md:col-span-2">
                    <label class="block mb-1 text-sm font-semibold text-gray-700">Catatan</label>
                    <textarea name="note" rows="2" placeholder="Tambahkan lokasi spesifik atau keterangan lainnya..."
                        class="w-full p-2.5 border rounded-lg focus:ring-2 focus:ring-green-500 outline-none"></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-8">
                <button type="button" onclick="closeCreateModal()"
                    class="px-6 py-2.5 text-sm font-semibold text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-all">
                    Batal
                </button>
                <button type="submit"
                    class="px-6 py-2.5 text-sm font-bold text-white bg-green-600 rounded-lg shadow-lg shadow-green-200 hover:bg-green-700 transition-all">
                    Simpan Sparepart
                </button>
            </div>
        </form>
    </div>
</div>



<script>
    function openCreateModal() {
        const modal = document.getElementById('modal-create');
        modal.classList.remove('hidden');
        // Prevent scroll on body
        document.body.style.overflow = 'hidden';
    }

    function closeCreateModal() {
        const modal = document.getElementById('modal-create');
        modal.classList.add('hidden');
        // Restore scroll
        document.body.style.overflow = 'auto';
    }

    // Close on outside click
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('modal-create');
        if (event.target == modal) {
            closeCreateModal();
        }
    });
</script>
