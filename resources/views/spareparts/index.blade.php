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

            {{-- ... bagian atas tetap sama ... --}}

            @if ($pendingApprovals->count() > 0 || $pendingReceipts->count() > 0)
                <div class="p-6 space-y-4">
                    {{-- Loop Request Masuk (Selalu tampil jika ada count) --}}
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

                    {{-- Loop Barang Datang (Hanya tampil jika user punya akses ke site ini) --}}
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

            {{-- ... bagian action bawah tetap sama ... --}}

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

                            {{-- TAMBAHKAN TOMBOL IMPORT DI SINI --}}
                            <button onclick="openImportModal()"
                                class="flex items-center gap-2 p-3 text-sm font-semibold text-white transition-all bg-blue-600 rounded-lg shadow-md hover:bg-blue-700">
                                <i class="fa-solid fa-file-import"></i>
                                Import Excel
                            </button>
                        @endif
                    </div>

                    <div class="flex w-full gap-2 md:w-auto md:ml-auto">
                        {{-- FILTER KONDISI --}}
                        <select id="filter-condition"
                            class="px-4 py-2 text-sm border rounded-lg outline-none focus:ring focus:ring-blue-200">
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
    <div id="modal-create"
        class="fixed inset-0 z-50 flex items-center justify-center hidden px-4 bg-black/60 backdrop-blur-sm">
        <div class="relative w-full max-w-2xl overflow-hidden bg-white shadow-2xl rounded-2xl">
            <div class="flex items-center justify-between px-6 py-4 border-b bg-gray-50">
                <h3 class="text-xl font-bold text-gray-800">Tambah Sparepart Baru</h3>
                <button onclick="closeCreateModal()" class="text-gray-400 transition-colors hover:text-red-500">
                    <i class="text-xl fa-solid fa-xmark"></i>
                </button>
            </div>
            <form action="{{ route('sparepart.store', $slug) }}" method="POST" enctype="multipart/form-data"
                class="p-6">
                @csrf
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="block mb-1 text-sm font-semibold text-gray-700">Item Name</label>
                        <input type="text" name="item_name" required placeholder="Contoh: Roller Conveyor"
                            class="w-full p-2.5 border rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                    </div>
                    <div>
                        <label class="block mb-1 text-sm font-semibold text-gray-700">Kategori</label>
                        <select name="category_id"
                            class="w-full p-2.5 border rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                            <option value="">-- Pilih Kategori --</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
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
                        <select name="uom" required class="w-full p-2.5 border rounded-lg outline-none">
                            <option value="PCS">PCS</option>
                            <option value="SET">SET</option>
                            <option value="UNIT">UNIT</option>
                        </select>
                    </div>
                    <div>
                        <label class="block mb-1 text-sm font-semibold text-gray-700">Jumlah Stok Awal</label>
                        <input type="number" name="qty" required min="1" value="1"
                            class="w-full p-2.5 border rounded-lg outline-none">
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
                    <button type="button" onclick="closeCreateModal()"
                        class="px-6 py-2.5 text-sm font-semibold text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200">Batal</button>
                    <button type="submit"
                        class="px-6 py-2.5 text-sm font-bold text-white bg-green-600 rounded-lg hover:bg-green-700">Simpan
                        Sparepart</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL IMPORT EXCEL --}}
    <div id="modal-import"
        class="fixed inset-0 z-50 flex items-center justify-center hidden bg-black/50 backdrop-blur-sm">
        <div class="w-full max-w-md p-6 bg-white shadow-2xl rounded-2xl">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-800">Import Sparepart via Excel</h3>
                <button onclick="closeImportModal()" class="text-gray-400 transition-colors hover:text-red-500">
                    <i class="text-xl fa-solid fa-xmark"></i>
                </button>
            </div>
            <form id="form-import" action="{{ route('sparepart.import', $slug) }}" method="POST"
                enctype="multipart/form-data">
                @csrf
                {{-- Drop Zone --}}
                <div id="import-dropzone"
                    class="flex flex-col items-center justify-center w-full h-32 transition-all border-2 border-gray-300 border-dashed cursor-pointer rounded-xl hover:border-blue-400 hover:bg-blue-50/50">
                    <i id="dropzone-icon" class="mb-2 text-3xl text-gray-400 fa-solid fa-cloud-arrow-up"></i>
                    <span id="dropzone-text" class="text-sm font-medium text-gray-500">Klik atau drag file ke sini</span>
                    <span id="dropzone-hint" class="mt-1 text-xs text-gray-400">.xlsx / .xls / .csv — max 10MB</span>
                    <input type="file" id="import-file-input" name="file" class="hidden"
                        accept=".xlsx,.xls,.csv">
                </div>

                {{-- Submit Button (hidden until file selected) --}}
                <button type="submit" id="btn-submit-import"
                    class="hidden w-full px-5 py-3 mt-4 text-sm font-bold text-white transition-all bg-blue-600 shadow-lg rounded-xl hover:bg-blue-700 shadow-blue-100">
                    <i class="mr-2 fa-solid fa-file-import"></i> Submit & Import Data
                </button>
            </form>
            <div class="p-3 mt-4 text-xs leading-relaxed text-blue-700 rounded-lg bg-blue-50">
                <p class="mb-1 font-semibold"><i class="mr-1 fa-solid fa-circle-info"></i> Format yang didukung:</p>
                <ul class="list-disc list-inside space-y-0.5">
                    <li>Kolom otomatis terdeteksi (Item/Name, Type/Model No., Quantity, Unit, dll.)</li>
                    <li>Semua sheet akan diproses otomatis</li>
                    <li>Baris kosong atau tidak valid akan di-skip</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- TOAST CONTAINER for import notifications --}}
    <div id="import-toast-container" class="fixed z-[60] top-5 right-5 space-y-3"></div>

    <script>
        // ── Toast System ──
        function showImportToast(message, type = 'info', duration = 0) {
            const container = document.getElementById('import-toast-container');
            const toast = document.createElement('div');

            const styles = {
                info: 'bg-blue-100 border-blue-300 text-blue-700',
                success: 'bg-green-100 border-green-300 text-green-700',
                error: 'bg-red-100 border-red-300 text-red-700',
            };

            const icons = {
                info: '<i class="mr-2 fa-solid fa-spinner fa-spin"></i>',
                success: '<i class="mr-2 fa-solid fa-circle-check"></i>',
                error: '<i class="mr-2 fa-solid fa-circle-xmark"></i>',
            };

            toast.className =
                `px-5 py-4 text-sm font-medium border shadow-xl rounded-xl transition-all duration-500 ${styles[type]} transform translate-y-3 opacity-0 scale-95`;
            toast.innerHTML = icons[type] + message;
            container.appendChild(toast);

            requestAnimationFrame(() => {
                toast.classList.remove('translate-y-3', 'opacity-0', 'scale-95');
                toast.classList.add('translate-y-0', 'opacity-100', 'scale-100');
            });

            if (duration > 0) {
                setTimeout(() => removeToast(toast), duration);
            }

            return toast;
        }

        function removeToast(toast) {
            toast.classList.add('opacity-0', 'translate-y-2', 'scale-95');
            setTimeout(() => toast.remove(), 500);
        }

        // ── Modal helpers ──
        function closeImportModal() {
            document.getElementById('modal-import').classList.add('hidden');
        }

        // ── Drop Zone: click to browse ──
        const dropzone = document.getElementById('import-dropzone');
        const fileInput = document.getElementById('import-file-input');

        dropzone.addEventListener('click', () => fileInput.click());

        // ── Drop Zone: drag & drop ──
        dropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropzone.classList.add('border-blue-400', 'bg-blue-50');
        });
        dropzone.addEventListener('dragleave', () => {
            dropzone.classList.remove('border-blue-400', 'bg-blue-50');
        });
        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.classList.remove('border-blue-400', 'bg-blue-50');
            const files = e.dataTransfer.files;
            if (files.length) {
                fileInput.files = files;
                fileInput.dispatchEvent(new Event('change'));
            }
        });

        // ── Step 1: File selected → show toast + reveal submit button ──
        fileInput.addEventListener('change', function() {
            if (!this.files.length) return;

            const fileName = this.files[0].name;

            // Update dropzone to show selected file
            document.getElementById('dropzone-icon').className =
                'fa-solid fa-file-excel text-3xl text-green-500 mb-2';
            document.getElementById('dropzone-text').textContent = fileName;
            document.getElementById('dropzone-text').classList.replace('text-gray-500', 'text-green-700');
            document.getElementById('dropzone-hint').textContent = 'File siap untuk diimport';
            document.getElementById('dropzone-hint').classList.replace('text-gray-400', 'text-green-500');
            dropzone.classList.remove('border-gray-300');
            dropzone.classList.add('border-green-400', 'bg-green-50/50');

            // Show submit button
            document.getElementById('btn-submit-import').classList.remove('hidden');

            // Toast: file ready
            showImportToast('File <b>' + fileName + '</b> siap diimport.', 'success', 4000);
        });

        // ── Step 2: Submit → AJAX import ──
        document.getElementById('form-import').addEventListener('submit', function(e) {
            e.preventDefault();

            const form = this;
            const formData = new FormData(form);
            const fileInput = document.getElementById('import-file-input');
            if (!fileInput.files.length) return;

            const fileName = fileInput.files[0].name;

            // Close modal
            closeImportModal();

            // Show processing toast
            const loadingToast = showImportToast('Mengimport data dari <b>' + fileName + '</b>...', 'info');

            const xhr = new XMLHttpRequest();

            xhr.addEventListener('load', function() {
                removeToast(loadingToast);

                try {
                    const result = JSON.parse(xhr.responseText);
                    if (xhr.status >= 200 && xhr.status < 300 && result.success) {
                        showImportToast(result.message, 'success', 5000);
                        setTimeout(() => window.location.reload(), 2000);
                    } else {
                        showImportToast(result.message || 'Terjadi kesalahan saat import.', 'error', 6000);
                    }
                } catch (err) {
                    if (xhr.status === 422) {
                        showImportToast('File tidak valid. Pastikan format .xlsx/.xls/.csv dan max 10MB.',
                            'error', 6000);
                    } else {
                        window.location.reload();
                    }
                }

                resetImportForm();
            });

            xhr.addEventListener('error', function() {
                removeToast(loadingToast);
                showImportToast('Gagal upload. Periksa koneksi jaringan.', 'error', 6000);
                resetImportForm();
            });

            xhr.open('POST', form.action);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.send(formData);
        });

        // ── Reset modal state ──
        function resetImportForm() {
            const form = document.getElementById('form-import');
            form.reset();
            document.getElementById('dropzone-icon').className = 'fa-solid fa-cloud-arrow-up text-3xl text-gray-400 mb-2';
            document.getElementById('dropzone-text').textContent = 'Klik atau drag file ke sini';
            document.getElementById('dropzone-text').classList.replace('text-green-700', 'text-gray-500');
            document.getElementById('dropzone-hint').textContent = '.xlsx / .xls / .csv — max 10MB';
            document.getElementById('dropzone-hint').classList.replace('text-green-500', 'text-gray-400');
            dropzone.classList.remove('border-green-400', 'bg-green-50/50');
            dropzone.classList.add('border-gray-300');
            document.getElementById('btn-submit-import').classList.add('hidden');
        }
    </script>

    {{-- MODAL EDIT --}}
    <div id="modal-edit"
        class="fixed inset-0 z-50 flex items-center justify-center hidden px-4 bg-black/60 backdrop-blur-sm">
        <div class="relative w-full max-w-2xl overflow-hidden bg-white shadow-2xl rounded-2xl">
            <div class="flex items-center justify-between px-6 py-4 border-b bg-gray-50">
                <h3 class="text-xl font-bold text-gray-800">Edit Sparepart</h3>
                <button onclick="closeEditModal()" class="text-gray-400 transition-colors hover:text-red-500">
                    <i class="text-xl fa-solid fa-xmark"></i>
                </button>
            </div>
            <form id="form-edit" method="POST" enctype="multipart/form-data" class="p-6">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="block mb-1 text-sm font-semibold text-gray-700">Item Name</label>
                        <input type="text" id="edit_item_name" name="item_name" required
                            class="w-full p-2.5 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                    <div>
                        <label class="block mb-1 text-sm font-semibold text-gray-700">Kategori</label>
                        <select id="edit_category_id" name="category_id"
                            class="w-full p-2.5 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                            <option value="">-- Pilih Kategori --</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block mb-1 text-sm font-semibold text-gray-700">Serial Number</label>
                        <input type="text" id="edit_serial_number" name="serial_number"
                            class="w-full p-2.5 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                    <div>
                        <label class="block mb-1 text-sm font-semibold text-gray-700">Type / Model</label>
                        <input type="text" id="edit_type" name="type" required
                            class="w-full p-2.5 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                    <div>
                        <label class="block mb-1 text-sm font-semibold text-gray-700">Satuan (UOM)</label>
                        <select id="edit_uom" name="uom" required
                            class="w-full p-2.5 border rounded-lg outline-none">
                            <option value="PCS">PCS</option>
                            <option value="SET">SET</option>
                            <option value="UNIT">UNIT</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block mb-1 text-sm font-semibold text-gray-700">Catatan / Note</label>
                        <textarea id="edit_note" name="note" rows="2"
                            class="w-full p-2.5 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block mb-1 text-sm font-semibold text-gray-700">Update Image (Opsional)</label>
                        <input type="file" name="image"
                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-8">
                    <button type="button" onclick="closeEditModal()"
                        class="px-6 py-2.5 text-sm font-semibold text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200">Batal</button>
                    <button type="submit"
                        class="px-6 py-2.5 text-sm font-bold text-white bg-blue-600 rounded-lg hover:bg-blue-700">Update
                        Sparepart</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(btn) {
            const item = JSON.parse(btn.getAttribute('data-item'));
            document.getElementById('modal-edit').classList.remove('hidden');

            document.getElementById('form-edit').action = `/sparepart/{{ $slug }}/${item.id}`;
            document.getElementById('edit_item_name').value = item.item_name;
            document.getElementById('edit_category_id').value = item.category_id || '';
            document.getElementById('edit_serial_number').value = item.serial_number || '';
            document.getElementById('edit_type').value = item.type;
            document.getElementById('edit_uom').value = item.uom;
            document.getElementById('edit_note').value = item.note || '';
        }

        function closeEditModal() {
            document.getElementById('modal-edit').classList.add('hidden');
        }

        function openImportModal() {
            const modal = document.getElementById('modal-import');
            modal.classList.remove('hidden');
            modal.classList.add('flex'); // Tambahkan flex agar modal berada di tengah
        }
    </script>
@endsection
