@extends('layout.master')

@section('content')
    <div class="px-6 py-8 mx-auto max-w-7xl">
        <div class="overflow-hidden bg-white border shadow-sm border-slate-200 rounded-3xl">

            {{-- 1. HEADER SECTION --}}
            <div class="px-8 py-8 border-b bg-slate-50/50 border-slate-100 md:flex md:items-center md:justify-between">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="p-1.5 bg-blue-100 text-blue-600 rounded-lg">
                            <i class="text-xs fa-solid fa-industry"></i>
                        </span>
                        <h2 class="text-2xl font-black tracking-tight uppercase text-slate-800">
                            {{ $siteData->machine_name }}
                        </h2>
                    </div>
                    <p class="text-sm font-semibold text-slate-500">
                        <i class="mr-1 fa-solid fa-location-dot"></i> {{ $siteData->branch->branch_name }}
                        <span class="mx-2 text-slate-300">•</span> Site Inventory Monitor
                    </p>
                </div>

                {{-- Quick Stats Mini (Optional) --}}
                <div class="flex gap-6 mt-4 border-l-0 md:mt-0 md:border-l border-slate-200 md:pl-8">
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Total SKU</p>
                        <p class="text-xl font-black text-slate-700">{{ $data->total() }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Status</p>
                        <p class="flex items-center text-sm font-bold text-emerald-500">
                            <span class="w-2 h-2 mr-2 rounded-full bg-emerald-500 animate-pulse"></span> Active
                        </p>
                    </div>
                </div>
            </div>

            {{-- 2. NOTIFICATION PANEL --}}
            @php
                // Ambil data transfer yang butuh Approval (Admin Site Asal)
                $pendingApprovals = \App\Models\SparepartTransfer::where('from_site_id', $siteData->id)
                    ->where('status', 'pending')
                    ->with('sparepart')
                    ->get();

                // Ambil data transfer yang butuh Receipt (Admin Site Tujuan)
                $pendingReceipts = \App\Models\SparepartTransfer::where('to_site_id', $siteData->id)
                    ->where('status', 'approved')
                    ->with('sparepart')
                    ->get();
            @endphp

            @if ($pendingApprovals->count() > 0 || $pendingReceipts->count() > 0)
                <div class="px-8 py-6 space-y-3 bg-white border-b border-slate-100">
                    <h4 class="mb-2 text-xs font-black tracking-widest uppercase text-slate-400">Attention Required</h4>

                    {{-- Section APPROVAL (Barang Keluar) --}}
                    @foreach ($pendingApprovals as $t)
                        <div
                            class="flex flex-col justify-between gap-4 p-4 border border-orange-100 md:flex-row md:items-center bg-orange-50 rounded-2xl">
                            <div class="flex items-center gap-4">
                                <div
                                    class="flex items-center justify-center w-10 h-10 text-orange-600 bg-orange-100 rounded-xl shrink-0">
                                    <i class="fa-solid fa-truck-ramp-box"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-extrabold text-orange-900">Transfer Request:
                                        {{ $t->sparepart->item_name }}</p>
                                    <p class="text-xs italic font-medium text-orange-700">
                                        Destination: {{ $t->toSite->machine_name }} •
                                        Qty: {{ $t->qty }} {{ $t->sparepart->uom }} •
                                        <span class="font-bold">Condition at Source:
                                            {{ strtoupper($t->from_condition) }}</span>
                                    </p>
                                </div>
                            </div>
                            <form action="{{ route('movement.approve', $t->id) }}" method="POST">
                                @csrf
                                <button type="submit"
                                    class="w-full md:w-auto px-6 py-2.5 text-xs font-black text-white bg-orange-600 rounded-xl hover:bg-orange-700 transition-all shadow-md shadow-orange-200">
                                    APPROVE & DEDUCT STOCK
                                </button>
                            </form>
                        </div>
                    @endforeach

                    {{-- Section RECEIPT (Barang Masuk) --}}
                    @if (Auth::user()->role === 'superadmin' ||
                            (Auth::user()->role === 'admin_site' && Auth::user()->site_id === $siteData->id))
                        @foreach ($pendingReceipts as $t)
                            <div
                                class="flex flex-col justify-between gap-4 p-4 border border-blue-100 md:flex-row md:items-center bg-blue-50 rounded-2xl">
                                <div class="flex items-center gap-4">
                                    <div
                                        class="flex items-center justify-center w-10 h-10 text-blue-600 bg-blue-100 rounded-xl shrink-0">
                                        <i class="fa-solid fa-box-archive"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-extrabold text-blue-900">Items In Transit:
                                            {{ $t->sparepart->item_name }}</p>
                                        <p class="text-xs italic font-medium text-blue-700">
                                            From: {{ $t->fromSite->machine_name }} •
                                            Incoming Condition: <span
                                                class="font-bold text-blue-800">{{ strtoupper($t->condition) }}</span>
                                        </p>
                                    </div>
                                </div>
                                <form action="{{ route('movement.receive', $t->id) }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                        class="w-full md:w-auto px-6 py-2.5 text-xs font-black text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition-all shadow-md shadow-blue-200">
                                        CONFIRM AS {{ strtoupper($t->condition) }}
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    @endif
                </div>
            @endif

            {{-- 3. TOOLBAR SECTION --}}
            <div class="px-8 py-6">
                <div class="flex flex-col justify-between gap-6 xl:flex-row xl:items-center">

                    {{-- Left Side: Actions --}}
                    <div class="flex flex-wrap gap-3">
                        @if (Auth::user()->role === 'superadmin' ||
                                (Auth::user()->role === 'admin_site' && Auth::user()->site_id === $siteData->id))
                            <button onclick="openCreateModal()"
                                class="flex items-center gap-2 px-5 py-3 text-xs font-black text-white transition-all shadow-lg bg-slate-900 rounded-xl hover:bg-black shadow-slate-200">
                                <i class="text-sm fa-solid fa-plus"></i> ADD SPAREPART
                            </button>

                            <button onclick="openImportModal()"
                                class="flex items-center gap-2 px-5 py-3 text-xs font-black text-blue-600 transition-all bg-blue-50 rounded-xl hover:bg-blue-100">
                                <i class="fa-solid fa-file-import"></i> IMPORT EXCEL
                            </button>

                            <a href="{{ route('sparepart.export', $slug) }}"
                                class="flex items-center gap-2 px-5 py-3 text-xs font-black transition-all text-emerald-600 bg-emerald-50 rounded-xl hover:bg-emerald-100">
                                <i class="fa-solid fa-file-export"></i> EXPORT
                            </a>
                        @endif
                    </div>

                    {{-- Right Side: Filters & Search --}}
                    <div class="flex flex-col items-center w-full gap-3 sm:flex-row xl:w-auto">
                        <div class="relative w-full sm:w-48 group">
                            <select id="filter-condition"
                                class="w-full py-3 pl-4 pr-10 text-xs font-bold transition-all border outline-none appearance-none text-slate-700 bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white">
                                <option value="">All Conditions</option>
                                <option value="new">NEW</option>
                                <option value="used-good">USED (Good)</option>
                                <option value="damaged">DAMAGED</option>
                                <option value="repair">REPAIR</option>
                            </select>
                            <i
                                class="fa-solid fa-chevron-down absolute right-4 top-3.5 text-slate-400 pointer-events-none text-[10px]"></i>
                        </div>

                        <div class="relative w-full sm:w-80 group">
                            <i
                                class="fa-solid fa-magnifying-glass absolute left-4 top-3.5 text-slate-400 group-focus-within:text-blue-500 transition-colors"></i>
                            <input type="text" id="search" data-route="{{ route('sparepart.index', $slug) }}"
                                placeholder="Search Name or Serial Number..."
                                class="w-full py-3 pr-4 text-xs font-bold transition-all border outline-none pl-11 text-slate-700 bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white">
                        </div>
                    </div>
                </div>
            </div>

            {{-- 4. TABLE SECTION --}}
            <div id="table-container" class="px-2 pb-6">
                @include('spareparts.table', ['assets' => $data, 'all_sites' => $all_sites])
            </div>
        </div>
    </div>

    {{-- MODAL CREATE --}}
    <div id="modal-create"
        class="fixed inset-0 z-50 flex items-center justify-center hidden px-4 bg-black/60 backdrop-blur-sm">
        <div class="relative w-full max-w-2xl overflow-hidden bg-white shadow-2xl rounded-2xl">
            <div class="flex items-center justify-between px-6 py-4 border-b bg-gray-50">
                <h3 class="text-xl font-bold text-gray-800">Add New Sparepart</h3>
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
                        <input type="text" name="item_name" required placeholder="Example: Roller Conveyor"
                            class="w-full p-2.5 border rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                    </div>
                    <div>
                        <label class="block mb-1 text-sm font-semibold text-gray-700">Category</label>
                        <select name="category_id"
                            class="w-full p-2.5 border rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                            <option value="">-- Select Category --</option>
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
                        <input type="text" name="type" required placeholder="Example: FS6000-X1"
                            class="w-full p-2.5 border rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                    </div>
                    <div>
                        <label class="block mb-1 text-sm font-semibold text-gray-700">Unit of Measure (UOM)</label>
                        <select name="uom" required class="w-full p-2.5 border rounded-lg outline-none">
                            <option value="PCS">PCS</option>
                            <option value="SET">SET</option>
                            <option value="UNIT">UNIT</option>
                        </select>
                    </div>
                    <div>
                        <label class="block mb-1 text-sm font-semibold text-gray-700">Initial Stock Quantity</label>
                        <input type="number" name="qty" required min="1" value="1"
                            class="w-full p-2.5 border rounded-lg outline-none">
                    </div>
                    <div>
                        <label class="block mb-1 text-sm font-semibold text-gray-700">Condition</label>
                        <select name="condition" required class="w-full p-2.5 border rounded-lg outline-none">
                            <option value="new">NEW</option>
                            <option value="used-good">USED (Good)</option>
                            <option value="damaged">DAMAGED</option>
                            <option value="repair">REPAIRED</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-8">
                    <button type="button" onclick="closeCreateModal()"
                        class="px-6 py-2.5 text-sm font-semibold text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200">Cancel</button>
                    <button type="submit"
                        class="px-6 py-2.5 text-sm font-bold text-white bg-green-600 rounded-lg hover:bg-green-700">Save
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
                <h3 class="text-xl font-bold text-gray-800">Import Spareparts via Excel</h3>
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
                    <span id="dropzone-text" class="text-sm font-medium text-gray-500">Click or drag file here</span>
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
                <p class="mb-1 font-semibold"><i class="mr-1 fa-solid fa-circle-info"></i> Supported formats:</p>
                <ul class="list-disc list-inside space-y-0.5">
                    <li>Columns automatically detected (Item/Name, Type/Model No., Quantity, Unit, etc.)</li>
                    <li>All sheets will be processed automatically</li>
                    <li>Empty or invalid rows will be skipped</li>
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
            document.getElementById('dropzone-hint').textContent = 'File ready to import';
            document.getElementById('dropzone-hint').classList.replace('text-gray-400', 'text-green-500');
            dropzone.classList.remove('border-gray-300');
            dropzone.classList.add('border-green-400', 'bg-green-50/50');

            // Show submit button
            document.getElementById('btn-submit-import').classList.remove('hidden');

            // Toast: file ready
            showImportToast('File <b>' + fileName + '</b> is ready to import.', 'success', 4000);
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
            const loadingToast = showImportToast('Importing data from <b>' + fileName + '</b>...', 'info');

            const xhr = new XMLHttpRequest();

            xhr.addEventListener('load', function() {
                removeToast(loadingToast);

                try {
                    const result = JSON.parse(xhr.responseText);
                    if (xhr.status >= 200 && xhr.status < 300 && result.success) {
                        showImportToast(result.message, 'success', 5000);
                        setTimeout(() => window.location.reload(), 2000);
                    } else {
                        showImportToast(result.message || 'An error occurred during import.', 'error',
                            6000);
                    }
                } catch (err) {
                    if (xhr.status === 422) {
                        showImportToast('Invalid file. Ensure format is .xlsx/.xls/.csv and max 10MB.',
                            'error', 6000);
                    } else {
                        window.location.reload();
                    }
                }

                resetImportForm();
            });

            xhr.addEventListener('error', function() {
                removeToast(loadingToast);
                showImportToast('Upload failed. Check your network connection.', 'error', 6000);
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
            document.getElementById('dropzone-text').textContent = 'Click or drag file here';
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
                        <label class="block mb-1 text-sm font-semibold text-gray-700">Category</label>
                        <select id="edit_category_id" name="category_id"
                            class="w-full p-2.5 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                            <option value="">-- Select Category --</option>
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
                        <label class="block mb-1 text-sm font-semibold text-gray-700">Unit of Measure (UOM)</label>
                        <select id="edit_uom" name="uom" required
                            class="w-full p-2.5 border rounded-lg outline-none">
                            <option value="PCS">PCS</option>
                            <option value="SET">SET</option>
                            <option value="UNIT">UNIT</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block mb-1 text-sm font-semibold text-gray-700">Note / Description</label>
                        <textarea id="edit_note" name="note" rows="2"
                            class="w-full p-2.5 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block mb-1 text-sm font-semibold text-gray-700">Update Image (Optional)</label>
                        <input type="file" name="image"
                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-8">
                    <button type="button" onclick="closeEditModal()"
                        class="px-6 py-2.5 text-sm font-semibold text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200">Cancel</button>
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
            modal.classList.add('flex'); // Keep it centered
        }
    </script>
@endsection
