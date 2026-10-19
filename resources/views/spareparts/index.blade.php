@extends('layout.master')

@section('title', 'Stok Sparepart per Lokasi')

@section('content')
    <div class="w-full space-y-6">

        {{-- 1. HEADER CARD --}}
        <div class="p-6 bg-white border shadow-xs sm:p-8 border-slate-200/80 rounded-3xl">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <div
                        class="inline-flex items-center gap-2 px-3 py-1 mb-2 text-xs font-bold text-blue-700 border border-blue-100 rounded-full bg-blue-50">
                        <i class="fa-solid fa-industry text-[10px]"></i> Unit Mesin
                    </div>
                    <h1 class="text-2xl font-extrabold tracking-tight uppercase sm:text-3xl text-slate-900">
                        {{ $siteData->machine_name }}
                    </h1>
                    <p class="flex items-center gap-2 mt-1 text-xs font-semibold sm:text-sm text-slate-500">
                        <span><i
                                class="mr-1 fa-solid fa-location-dot text-slate-400"></i>{{ $siteData->branch->branch_name ?? 'Cabang Belum Ditentukan' }}</span>
                        <span class="text-slate-300">•</span>
                        <span>Pemantauan Stok Lokasi</span>
                    </p>
                </div>

                {{-- Quick Stats Mini --}}
                <div
                    class="flex items-center gap-6 pt-4 border-t md:pt-0 md:border-t-0 md:border-l border-slate-200 md:pl-8">
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Jumlah Jenis Barang</p>
                        <p class="text-2xl font-black text-slate-800">{{ $data->total() }}</p>
                    </div>
                    <div class="w-px h-8 bg-slate-200"></div>
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Tahap Status</p>
                        <p class="flex items-center mt-1 text-xs font-bold text-emerald-600">
                            <span class="w-2 h-2 mr-2 rounded-full bg-emerald-500 animate-pulse"></span> Operasional
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. NOTIFICATION PANEL (PENDING MOVEMENT APPROVALS) --}}
        @php
            $pendingApprovals = $pendingApprovals ?? [];
            $pendingReceipts = $pendingReceipts ?? [];
        @endphp

        @if (count($pendingApprovals) > 0 || count($pendingReceipts) > 0)
            <div class="p-6 space-y-3 bg-white border shadow-xs border-amber-200/80 rounded-3xl">
                <div class="flex items-center gap-2 mb-1">
                    <i class="text-xs fa-solid fa-bell text-amber-500"></i>
                    <h4 class="text-xs font-extrabold tracking-wider uppercase text-slate-400">Perlu Diperiksa</h4>
                </div>

                @foreach ($pendingApprovals as $t)
                    <div
                        class="flex flex-col justify-between gap-4 p-4 border border-amber-200 bg-amber-50/60 rounded-2xl md:flex-row md:items-center">
                        <div class="flex items-center gap-3.5">
                            <div
                                class="flex items-center justify-center w-10 h-10 font-bold text-amber-600 bg-amber-100/80 rounded-xl shrink-0">
                                <i class="text-base fa-solid fa-truck-ramp-box"></i>
                            </div>
                            <div>
                                <p class="text-xs font-extrabold sm:text-sm text-amber-950">Pengajuan Stok Keluar:
                                    {{ $t->sparepart?->item_name }}</p>
                                <p class="text-xs sm:text-xs font-medium text-amber-800 mt-0.5">
                                    Tujuan: <strong>{{ $t->toSite?->machine_name }}</strong> &bull; Jumlah:
                                    <strong>{{ $t->qty }} {{ $t->sparepart?->uom }}</strong> &bull;
                                    Kondisi: <span class="font-bold uppercase">{{ $t->from_condition }}</span>
                                </p>
                            </div>
                        </div>
                        <form action="{{ route('movement.approve', $t->id) }}" method="POST">
                            @csrf
                            <button type="submit"
                                class="w-full px-5 py-2.5 text-xs font-bold text-white transition-all bg-amber-600 hover:bg-amber-700 rounded-xl shadow-md shadow-amber-600/20 active:scale-95 shrink-0 cursor-pointer">
                                Setujui dan Kurangi Stok
                            </button>
                        </form>
                    </div>
                @endforeach

                @if (in_array(Auth::user()?->role, ['superadmin', 'team_leader']))
                    @foreach ($pendingReceipts as $t)
                        <div
                            class="flex flex-col justify-between gap-4 p-4 border border-blue-200 bg-blue-50/60 rounded-2xl md:flex-row md:items-center">
                            <div class="flex items-center gap-3.5">
                                <div
                                    class="flex items-center justify-center w-10 h-10 font-bold text-blue-600 bg-blue-100/80 rounded-xl shrink-0">
                                    <i class="text-base fa-solid fa-box-archive"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-extrabold text-blue-950 sm:text-sm">Stok Masuk dalam Pengiriman:
                                        {{ $t->sparepart?->item_name }}</p>
                                    <p class="text-xs sm:text-xs font-medium text-blue-800 mt-0.5">
                                        Asal: <strong>{{ $t->fromSite?->machine_name }}</strong> &bull; Kondisi Stok Masuk:
                                        <span class="font-bold uppercase">{{ \App\Support\UiText::label($t->condition) }}</span>
                                    </p>
                                </div>
                            </div>
                            <form action="{{ route('movement.receive', $t->id) }}" method="POST">
                                @csrf
                                <button type="submit"
                                    class="w-full px-5 py-2.5 text-xs font-bold text-white transition-all bg-blue-600 hover:bg-blue-700 rounded-xl shadow-md shadow-blue-600/20 active:scale-95 shrink-0 cursor-pointer">
                                    Konfirmasi Penerimaan
                                </button>
                            </form>
                        </div>
                    @endforeach
                @endif
            </div>
        @endif

        {{-- 3. TABLE CARD --}}
        <div class="overflow-hidden bg-white border shadow-xs border-slate-200/80 rounded-3xl">

            {{-- TOOLBAR SECTION --}}
            <div class="p-5 border-b sm:p-6 border-slate-100 bg-slate-50/30">
                <div class="flex flex-col justify-between gap-4 xl:flex-row xl:items-center">

                    {{-- Action Buttons --}}
                    <div class="flex flex-wrap items-center gap-2.5">
                        @if (Auth::user()?->role === 'superadmin' ||
                                (Auth::user()?->role === 'team_leader' && Auth::user()?->site_id === $siteData->id))
                            <button onclick="openCreateModal()" type="button"
                                class="flex items-center gap-2 px-4 py-2.5 text-xs font-bold text-white transition-all bg-slate-900 hover:bg-blue-600 rounded-xl shadow-md active:scale-95 cursor-pointer">
                                <i class="text-xs fa-solid fa-plus"></i> Tambah Suku Cadang
                            </button>
                            <button onclick="openImportModal()" type="button"
                                class="flex items-center gap-2 px-4 py-2.5 text-xs font-bold text-blue-700 transition-all bg-blue-50 border border-blue-100 rounded-xl hover:bg-blue-600 hover:text-white active:scale-95 cursor-pointer">
                                <i class="fa-solid fa-file-import"></i> Impor Excel
                            </button>
                            <a href="{{ route('sparepart.export', $slug) }}"
                                class="flex items-center gap-2 px-4 py-2.5 text-xs font-bold text-emerald-700 transition-all bg-emerald-50 border border-emerald-100 rounded-xl hover:bg-emerald-600 hover:text-white active:scale-95">
                                <i class="fa-solid fa-file-export"></i> Ekspor Data
                            </a>
                        @endif
                    </div>

                    {{-- Filters & Search --}}
                    <div class="flex flex-col items-center w-full gap-3 sm:flex-row xl:w-auto">
                        <div class="relative w-full sm:w-48">
                            <select id="filter-condition"
                                class="w-full py-2.5 pl-3.5 pr-10 text-xs font-bold transition-all border outline-none appearance-none text-slate-700 bg-white border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 shadow-2xs cursor-pointer">
                                <option value="">Semua Kondisi</option>
                                <option value="new">Baru</option>
                                <option value="used-good">Bekas (Baik)</option>
                                <option value="damaged">Rusak</option>
                                <option value="repair">Perbaikan</option>
                            </select>
                            <i
                                class="fa-solid fa-chevron-down absolute right-3.5 top-3.5 text-slate-400 pointer-events-none text-[10px]"></i>
                        </div>

                        <div class="relative w-full sm:w-72">
                            <span
                                class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 pointer-events-none">
                                <i class="text-xs fa-solid fa-magnifying-glass"></i>
                            </span>
                            <input type="text" name="search" id="search" placeholder="Cari nama atau nomor seri..."
                                value="{{ request('search') }}" autocomplete="off"
                                class="w-full py-2.5 pl-10 pr-3.5 text-xs sm:text-sm font-medium border-slate-200 rounded-xl bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none border shadow-2xs">
                        </div>
                    </div>
                </div>
            </div>

            {{-- TABLE CONTAINER SECTION --}}
            <div id="table-container" class="min-h-[300px] transition-opacity duration-200">
                @include('spareparts.table', ['assets' => $data, 'all_sites' => $all_sites])
            </div>
        </div>
    </div>

    {{-- MODAL REUSABLE BASE STYLE --}}
    @php $modalBase = "fixed inset-0 z-50 flex items-center justify-center hidden p-4 bg-slate-900/60 backdrop-blur-xs transition-all duration-300"; @endphp

    {{-- MODAL CREATE --}}
    <div id="modal-create" class="{{ $modalBase }}">
        <div
            class="relative w-full max-w-2xl overflow-hidden bg-white shadow-2xl rounded-3xl border border-slate-100 max-h-[90vh] flex flex-col">
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100 shrink-0">
                <div class="flex items-center gap-3">
                    <div
                        class="flex items-center justify-center w-10 h-10 border text-emerald-600 bg-emerald-50 rounded-2xl border-emerald-100">
                        <i class="text-base fa-solid fa-boxes-stacked"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900">Tambah Suku Cadang</h3>
                        <p class="text-xs font-bold tracking-wider uppercase text-slate-400">Stok Masuk &bull;
                            {{ $siteData->machine_name ?? 'Site' }}</p>
                    </div>
                </div>
                <button onclick="closeCreateModal()" type="button"
                    class="flex items-center justify-center w-8 h-8 transition-colors rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                    <i class="text-base fa-solid fa-xmark"></i>
                </button>
            </div>

            <form action="{{ route('sparepart.store', $slug) }}" method="POST" enctype="multipart/form-data"
                class="p-6 space-y-4 overflow-y-auto">
                @csrf
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Nama Barang <span
                                class="text-rose-500">*</span></label>
                        <input type="text" name="item_name" required placeholder="Contoh: Roller Conveyor"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition-all font-medium text-slate-800">
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Kategori</label>
                        <select name="category_id"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition-all font-medium text-slate-800">
                            <option value="">-- Pilih Kategori --</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Nomor Seri</label>
                        <input type="text" name="serial_number" placeholder="SN-XXXXX (Opsional)"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition-all font-medium text-slate-800">
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Jenis / Model <span
                                class="text-rose-500">*</span></label>
                        <input type="text" name="type" required placeholder="Contoh: FS6000-X1"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition-all font-medium text-slate-800">
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Jumlah dan Satuan <span class="text-rose-500">*</span></label>
                        <div class="flex gap-0">
                            <input type="number" name="qty" required min="1" value="1"
                                class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-r-0 border-slate-200 rounded-l-xl bg-slate-50 focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition-all font-bold text-slate-800">
                            <select name="uom" required
                                class="w-28 px-2 py-2.5 text-xs font-bold border border-slate-200 bg-slate-100 rounded-r-xl outline-none focus:ring-4 focus:ring-emerald-500/10 text-slate-700">
                                <option value="PCS" selected>PCS</option>
                                <option value="SET">SET</option>
                                <option value="UNIT">UNIT</option>
                            </select>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Kondisi <span
                                class="text-rose-500">*</span></label>
                        <select name="condition" required
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition-all font-medium text-slate-800">
                            <option value="" disabled selected>Pilih Kondisi</option>
                            <option value="new">Baru</option>
                            <option value="used-good">Bekas (Baik)</option>
                            <option value="damaged">Rusak</option>
                            <option value="repair">Hasil Perbaikan</option>
                        </select>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Foto Suku Cadang</label>
                    <div class="flex flex-col items-center justify-center w-full gap-3">
                        <label id="upload-label"
                            class="flex flex-col items-center justify-center w-full transition-colors border-2 border-dashed cursor-pointer h-28 rounded-2xl border-slate-200 bg-slate-50 hover:bg-slate-100">
                            <div class="flex flex-col items-center justify-center py-4">
                                <i class="mb-1 text-xl fa-solid fa-cloud-arrow-up text-slate-400"></i>
                                <p class="text-xs font-bold text-slate-600">Klik atau tarik file untuk mengunggah</p>
                                <p class="text-xs text-slate-400 uppercase mt-0.5">PNG, JPG, WEBP (Maksimal 2MB)</p>
                            </div>
                            <input type="file" name="image" id="image-input" class="hidden" accept="image/*"
                                onchange="previewImage(this)" />
                        </label>

                        <div id="preview-container" class="relative hidden w-full">
                            <img id="image-preview" src="#" alt="Preview"
                                class="object-contain w-full h-40 border-2 border-emerald-500 rounded-2xl bg-slate-50">
                            <button type="button" onclick="resetImage()"
                                class="absolute flex items-center justify-center text-white rounded-full shadow-md w-7 h-7 bg-rose-600 top-2 right-2 hover:bg-rose-700">
                                <i class="text-xs fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Catatan / Keterangan</label>
                    <textarea name="note" rows="2" placeholder="Tambahkan keterangan..."
                        class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition-all text-slate-800 font-medium"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                    <button type="button" onclick="closeCreateModal()"
                        class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-800 transition-colors">Batal</button>
                    <button type="submit"
                        class="px-6 py-2.5 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-md shadow-emerald-600/20 active:scale-[0.98] transition-all">
                        <i class="mr-1.5 fa-solid fa-floppy-disk"></i> Simpan Suku Cadang
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL IMPORT EXCEL --}}
    <div id="modal-import" class="{{ $modalBase }}">
        <div class="w-full max-w-md p-6 bg-white border shadow-2xl border-slate-100 rounded-3xl">
            <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
                <h3 class="text-base font-extrabold text-slate-900">Impor Suku Cadang dari Excel</h3>
                <button onclick="closeImportModal()" type="button" class="text-slate-400 hover:text-slate-600">
                    <i class="text-base fa-solid fa-xmark"></i>
                </button>
            </div>
            <form id="form-import" action="{{ route('sparepart.import', $slug) }}" method="POST"
                enctype="multipart/form-data">
                @csrf
                <div id="import-dropzone" onclick="document.getElementById('import-file-input').click()"
                    class="flex flex-col items-center justify-center w-full transition-all border-2 border-dashed cursor-pointer h-36 border-slate-200 rounded-2xl hover:border-blue-500 hover:bg-blue-50/50">
                    <i id="dropzone-icon" class="mb-2 text-3xl text-slate-400 fa-solid fa-cloud-arrow-up"></i>
                    <span id="dropzone-text" class="px-4 text-xs font-bold text-center text-slate-700">Klik atau tarik file Excel ke sini</span>
                    <span id="dropzone-hint" class="mt-1 text-xs text-slate-400">.xlsx / .xls / .csv &bull; Maksimal
                        10MB</span>
                    <input type="file" id="import-file-input" name="file" class="hidden" accept=".xlsx,.xls,.csv"
                        required onchange="handleImportFileSelect(this)">
                </div>
                <button type="submit" id="btn-submit-import"
                    class="hidden w-full px-5 py-3 mt-4 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-md shadow-blue-600/20 active:scale-[0.98] transition-all">
                    <i class="mr-1.5 fa-solid fa-file-import"></i> Impor Data
                </button>
            </form>
        </div>
    </div>

    {{-- MODAL EDIT --}}
    <div id="modal-edit" class="{{ $modalBase }}">
        <div
            class="relative w-full max-w-2xl overflow-hidden bg-white shadow-2xl rounded-3xl border border-slate-100 max-h-[90vh] flex flex-col">
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100 shrink-0">
                <div class="flex items-center gap-3">
                    <div
                        class="flex items-center justify-center w-10 h-10 text-blue-600 border border-blue-100 bg-blue-50 rounded-2xl">
                        <i class="text-base fa-solid fa-pen-to-square"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900">Ubah Suku Cadang</h3>
                        <p class="text-xs font-bold tracking-wider uppercase text-slate-400">Inventory Management
                            &bull; Perbarui Data</p>
                    </div>
                </div>
                <button onclick="closeEditModal()" type="button"
                    class="flex items-center justify-center w-8 h-8 transition-colors rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                    <i class="text-base fa-solid fa-xmark"></i>
                </button>
            </div>

            <form id="form-edit" method="POST" enctype="multipart/form-data" class="p-6 space-y-4 overflow-y-auto">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Nama Barang</label>
                        <input type="text" id="edit_item_name" name="item_name" required
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all font-medium text-slate-800">
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Jumlah</label>
                        <input type="number" id="edit_qty" name="qty" required min="0"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all font-bold text-slate-800">
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Kategori</label>
                        <select id="edit_category_id" name="category_id"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all font-medium text-slate-800">
                            <option value="">-- Pilih Kategori --</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Nomor Seri</label>
                        <input type="text" id="edit_serial_number" name="serial_number"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all font-medium text-slate-800">
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Jenis / Model</label>
                        <input type="text" id="edit_type" name="type" required
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all font-medium text-slate-800">
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Satuan</label>
                        <select id="edit_uom" name="uom" required
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all font-medium text-slate-800">
                            <option value="PCS">PCS</option>
                            <option value="SET">SET</option>
                            <option value="UNIT">UNIT</option>
                        </select>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Foto Suku Cadang</label>
                    <div class="flex flex-col items-center justify-center w-full gap-3">
                        <label id="edit-upload-label"
                            class="flex flex-col items-center justify-center w-full transition-colors border-2 border-dashed cursor-pointer h-28 rounded-2xl border-slate-200 bg-slate-50 hover:bg-slate-100">
                            <div class="flex flex-col items-center justify-center py-4">
                                <i class="mb-1 text-xl fa-solid fa-cloud-arrow-up text-slate-400"></i>
                                <p class="text-xs font-bold text-slate-600">Klik atau tarik file untuk mengunggah</p>
                            </div>
                            <input type="file" name="image" id="edit-image-input" class="hidden" accept="image/*"
                                onchange="previewEditImage(this)" />
                        </label>

                        <div id="edit-preview-container" class="relative hidden w-full">
                            <img id="edit-image-preview" src="#" alt="Preview"
                                class="object-contain w-full h-40 border-2 border-blue-500 rounded-2xl bg-slate-50">
                            <button type="button" onclick="resetEditImage()"
                                class="absolute flex items-center justify-center text-white rounded-full shadow-md w-7 h-7 bg-rose-600 top-2 right-2 hover:bg-rose-700">
                                <i class="text-xs fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Catatan / Keterangan</label>
                    <textarea id="edit_note" name="note" rows="2"
                        class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all font-medium text-slate-800"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                    <button type="button" onclick="closeEditModal()"
                        class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-800 transition-colors">Batal</button>
                    <button type="submit"
                        class="px-6 py-2.5 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-md shadow-blue-600/20 active:scale-[0.98] transition-all">
                        Perbarui Suku Cadang
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL ADJUSTMENT STOK --}}
    <div id="modal-adjust" class="{{ $modalBase }}">
        <div
            class="relative w-full max-w-md overflow-hidden bg-white shadow-2xl rounded-3xl border border-slate-100 max-h-[90vh] flex flex-col">
            <div class="px-6 py-5 border-b border-slate-100 shrink-0">
                <div class="flex items-center gap-3">
                    <div
                        class="flex items-center justify-center w-10 h-10 text-blue-600 border border-blue-100 rounded-2xl bg-blue-50">
                        <i class="text-base fa-solid fa-sliders"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-base font-extrabold text-slate-900">Sesuaikan Kondisi / Stok</h3>
                        <p id="adjust-item-name"
                            class="text-xs font-bold text-slate-400 uppercase truncate max-w-[220px]"></p>
                    </div>
                </div>
            </div>

            <form id="form-adjust" method="POST" class="p-6 space-y-4 overflow-y-auto">
                @csrf
                <input type="hidden" name="current_condition" id="input-current-condition">

                <div class="grid grid-cols-2 gap-3 p-3.5 text-center border bg-slate-50 border-slate-200/80 rounded-2xl">
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Kondisi Saat Ini</p>
                        <span id="display-current-condition"
                            class="inline-block mt-1 px-2.5 py-0.5 rounded-lg text-xs font-black bg-slate-200 text-slate-700 uppercase"></span>
                    </div>
                    <div class="border-l border-slate-200">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Stok Tersedia</p>
                        <p id="current-stock-display" class="mt-0.5 text-sm font-black text-slate-900"></p>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Jumlah yang Disesuaikan</label>
                    <div class="relative">
                        <input type="number" name="qty_to_move" id="input-qty-adjust" required min="1"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-bold bg-white border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none text-slate-800">
                        <span id="adjust-uom-badge"
                            class="absolute right-3.5 top-3 text-xs font-bold text-slate-400 uppercase">Unit</span>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Kondisi Baru</label>
                    <div class="relative">
                        <select name="new_condition" id="select-condition-adjust" required
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-bold bg-white border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none appearance-none text-slate-800">
                            <option value="new">Baru</option>
                            <option value="used-good">Bekas (Baik)</option>
                            <option value="damaged">Rusak</option>
                            <option value="repair">Hasil Perbaikan</option>
                        </select>
                        <i
                            class="fa-solid fa-chevron-down absolute right-3.5 top-3.5 text-slate-400 pointer-events-none text-[10px]"></i>
                    </div>
                </div>

                <div id="adjustment-hint-box"
                    class="flex gap-2.5 p-3.5 text-xs leading-normal text-blue-800 border border-blue-100 bg-blue-50 rounded-2xl">
                    <i class="fa-solid fa-circle-info mt-0.5 shrink-0 text-blue-600"></i>
                    <span id="adjustment-hint-text">Masukkan jumlah untuk melihat hasil penyesuaian.</span>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                    <button type="button" onclick="closeAdjustModal()"
                        class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-800 transition-colors">Batal</button>
                    <button type="submit" id="btn-submit-adjust"
                        class="px-5 py-2.5 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-md shadow-blue-600/20 active:scale-[0.98] transition-all">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL DETAIL VIEW --}}
    <div id="detailModal"
        class="fixed inset-0 z-50 flex items-center justify-center hidden p-4 transition-all duration-300 bg-slate-900/60 backdrop-blur-xs">
        <div id="modalWrapper"
            class="w-full max-w-3xl overflow-hidden transition-all transform scale-95 opacity-0 bg-white border border-slate-100 shadow-2xl rounded-3xl max-h-[90vh] flex flex-col">

            <div class="flex items-center justify-between p-6 border-b border-slate-100 bg-slate-50/50 shrink-0">
                <div class="flex items-center gap-4">
                    <div class="relative group shrink-0">
                        <div id="d_image_container"
                            class="relative w-20 h-20 overflow-hidden transition-all bg-white border cursor-pointer border-slate-200 shadow-2xs rounded-2xl hover:ring-2 hover:ring-blue-500"
                            onclick="expandImage()">
                            <img id="d_image"
                                class="object-cover w-full h-full transition-all duration-500 group-hover:scale-110">
                            <div
                                class="absolute inset-0 z-10 flex flex-col items-center justify-center transition-all duration-300 opacity-0 pointer-events-none bg-black/40 group-hover:opacity-100">
                                <i class="text-xs text-white fa-solid fa-magnifying-glass-plus"></i>
                            </div>
                        </div>

                        <div id="no-image-placeholder"
                            class="flex flex-col items-center justify-center hidden w-20 h-20 border border-dashed text-slate-300 border-slate-300 bg-slate-50 rounded-2xl">
                            <i class="mb-1 text-xl opacity-50 fa-solid fa-image-slash"></i>
                            <span class="text-xs font-bold uppercase tracking-widest opacity-60">Tidak Ada Foto</span>
                        </div>
                    </div>
                    <div>
                        <h3 id="d_item_name" class="text-lg font-extrabold leading-snug text-slate-900"></h3>
                        <p id="d_type" class="font-mono text-xs font-semibold text-slate-500 mt-0.5"></p>
                        <div class="flex flex-wrap gap-1.5 mt-2">
                            <span id="d_serial_number"
                                class="px-2.5 py-0.5 bg-slate-100 border border-slate-200 text-slate-700 text-xs rounded-lg font-bold font-mono"></span>
                            <span id="d_source_data"
                                class="px-2.5 py-0.5 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs rounded-lg font-bold"></span>
                        </div>
                    </div>
                </div>
                <button onclick="closeDetailModal()" type="button"
                    class="flex items-center justify-center w-8 h-8 transition-colors rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                    <i class="text-base fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="grid grid-cols-1 overflow-y-auto text-xs md:grid-cols-2">
                <div class="p-6 border-b md:border-b-0 md:border-r border-slate-100">
                    <p class="flex items-center gap-2 mb-3 font-bold text-slate-800 uppercase tracking-wider text-xs">
                        <i class="text-blue-600 fa-solid fa-layer-group"></i> Sebaran Stok per Lokasi
                    </p>
                    <div class="overflow-hidden border border-slate-200/80 rounded-2xl">
                        <table class="w-full text-xs text-left">
                            <thead
                                class="font-bold text-slate-500 bg-slate-50 border-b border-slate-100 text-xs uppercase">
                                <tr>
                                    <th class="p-3">Lokasi Mesin</th>
                                    <th class="p-3 text-center">Jumlah</th>
                                    <th class="p-3 text-center">Kondisi</th>
                                </tr>
                            </thead>
                            <tbody id="d_stock_table" class="font-medium divide-y divide-slate-100 text-slate-700">
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="p-6 bg-slate-50/50">
                    <p class="flex items-center gap-2 mb-4 font-bold text-slate-800 uppercase tracking-wider text-xs">
                        <i class="text-amber-600 fa-solid fa-clock-rotate-left"></i> Riwayat Perpindahan Stok
                    </p>
                    <div class="relative pl-5 border-l-2 border-amber-300 space-y-4 max-h-[250px] overflow-y-auto pr-2"
                        id="d_history"></div>
                </div>
            </div>

            <div class="p-4 text-right bg-white border-t border-slate-100 shrink-0">
                <button onclick="closeDetailModal()" type="button"
                    class="px-5 py-2.5 text-xs font-bold transition-colors text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl">
                    Tutup Rincian
                </button>
            </div>
        </div>
    </div>

    {{-- MODAL DELETE --}}
    <div id="modal-delete"
        class="fixed inset-0 z-[100] flex items-center justify-center hidden p-4 bg-slate-900/60 backdrop-blur-xs transition-all">
        <div
            class="relative w-full max-w-sm p-6 text-center transition-all duration-300 transform scale-95 bg-white border shadow-2xl opacity-0 border-slate-100 modal-content rounded-3xl">
            <div class="flex justify-center mb-4">
                <div
                    class="flex items-center justify-center w-12 h-12 border rounded-2xl bg-rose-50 border-rose-100 text-rose-600">
                    <i class="text-xl fa-solid fa-triangle-exclamation"></i>
                </div>
            </div>
            <h3 class="mb-1 text-base font-extrabold text-slate-900">Apakah Anda yakin?</h3>
            <p class="mb-6 text-xs font-medium text-slate-500">Anda akan menghapus <strong id="delete-item-name"
                    class="text-slate-900"></strong>.</p>
            <form id="form-confirm-delete" method="POST">
                @csrf @method('DELETE')
                <div class="flex items-center justify-end gap-3">
                    <button type="button" onclick="closeDeleteModal()"
                        class="w-full py-2.5 text-xs font-bold text-slate-600 hover:text-slate-800 transition-colors">
                        Batal
                    </button>
                    <button type="submit"
                        class="w-full py-2.5 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 active:scale-[0.98] transition-all rounded-xl shadow-md shadow-rose-600/20">
                        Ya, Hapus
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL MOVE --}}
    <div id="modal-move"
        class="fixed inset-0 z-[60] flex items-center justify-center hidden p-4 bg-slate-900/60 backdrop-blur-xs transition-all">
        <div
            class="w-full max-w-md overflow-hidden bg-white border border-slate-100 shadow-2xl rounded-3xl max-h-[90vh] flex flex-col">
            <div class="flex items-center gap-3 px-6 py-4 border-b border-amber-100 bg-amber-50/60 shrink-0">
                <div class="p-2.5 text-amber-700 bg-amber-100/80 rounded-2xl"><i
                        class="text-base fa-solid fa-truck-fast"></i></div>
                <div>
                    <h3 class="text-base font-extrabold text-slate-900">Pengajuan Pemindahan</h3>
                    <p id="move-asset-tag" class="font-mono text-xs font-bold text-amber-700 uppercase"></p>
                </div>
            </div>
            <form id="form-move" method="POST" class="p-6 space-y-4 overflow-y-auto">
                @csrf
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Lokasi Tujuan</label>
                    <select name="to_site_id" required
                        class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-bold border border-slate-200 bg-slate-50 rounded-xl focus:bg-white focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 outline-none transition-all text-slate-800">
                        <option value="" disabled selected>Pilih Tujuan</option>
                        @foreach ($all_sites as $s)
                            @if ($s->id !== $siteData->id)
                                <option value="{{ $s->id }}">{{ $s->machine_name }}
                                    ({{ $s->branch->branch_name ?? 'Branch' }})
                                </option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Kondisi</label>
                        <select name="condition" id="target-condition" required
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-bold border border-slate-200 bg-slate-50 rounded-xl outline-none text-slate-800">
                            <option value="new">Baru</option>
                            <option value="used-good">Bekas Baik</option>
                            <option value="damaged">Rusak</option>
                            <option value="repair">Hasil Perbaikan</option>
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Jumlah</label>
                        <input type="number" name="qty" id="move-quantity" min="1" required
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs sm:text-sm font-bold text-slate-800">
                    </div>
                </div>
                <p id="max-info" class="text-xs font-bold text-right text-slate-400 italic"></p>
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                    <button type="button" onclick="closeMoveModal()"
                        class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-800 transition-colors">
                        Batal
                    </button>
                    <button type="submit"
                        class="px-5 py-2.5 text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 rounded-xl shadow-md shadow-amber-600/20 active:scale-[0.98] transition-all">
                        Ajukan Pemindahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- IMAGE VIEWER MODAL --}}
    <div id="image-viewer"
        class="fixed inset-0 z-[100] hidden bg-slate-950/90 backdrop-blur-md items-center justify-center p-4">
        <div class="relative flex items-center justify-center w-full h-full">
            <h2 class="sr-only">Pratinjau gambar suku cadang</h2>
        <button onclick="closeImageViewer()" type="button"
            class="absolute z-10 p-2 text-white transition-transform hover:text-slate-300 top-4 right-4 hover:scale-110">
            <i class="text-3xl fa-solid fa-xmark"></i>
        </button>
        <img id="full-image" src=""
            class="max-w-full max-h-full transition-all duration-300 transform scale-95 shadow-2xl rounded-2xl"
            alt="Pratinjau gambar suku cadang">
        </div>
    </div>
@endsection

@push('scripts')
    <script type="application/json" id="spareparts-config">{!! json_encode(['slug' => $slug, 'indexUrl' => route('sparepart.index', $siteData->slug)], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    @vite('resources/js/pages/spareparts.js')
@endpush
