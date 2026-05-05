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
        class="fixed inset-0 z-50 flex items-center justify-center hidden px-4 transition-all duration-300 bg-slate-900/60 backdrop-blur-sm">
        <div class="relative w-full max-w-3xl overflow-hidden transition-all transform bg-white shadow-2xl rounded-3xl">
            {{-- Header --}}
            <div class="flex items-center justify-between px-8 py-6 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-12 h-12 text-emerald-600 bg-emerald-50 rounded-2xl">
                        <i class="text-xl fa-solid fa-boxes-stacked"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-black tracking-tight text-slate-800">Add New Sparepart</h3>
                        <p class="text-xs font-bold tracking-wider uppercase text-slate-400">Inventory Inbound &bull;
                            {{ $siteData->name ?? 'Site' }}</p>
                    </div>
                </div>
                <button onclick="closeCreateModal()"
                    class="flex items-center justify-center w-10 h-10 transition-colors rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                    <i class="text-lg fa-solid fa-xmark"></i>
                </button>
            </div>

            <form action="{{ route('sparepart.store', $slug) }}" method="POST" enctype="multipart/form-data"
                class="max-h-[80vh] overflow-y-auto">
                @csrf
                <div class="p-8 space-y-6">
                    {{-- Grid Utama --}}
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

                        {{-- Item Name --}}
                        <div class="space-y-2">
                            <label class="text-[11px] font-black uppercase tracking-widest text-slate-500 ml-1">Item
                                Name</label>
                            <input type="text" name="item_name" required placeholder="Example: Roller Conveyor"
                                class="w-full px-4 py-3 text-sm font-bold transition-all border outline-none border-slate-200 bg-slate-50 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:bg-white text-slate-700">
                        </div>

                        {{-- Category --}}
                        <div class="space-y-2">
                            <label
                                class="text-[11px] font-black uppercase tracking-widest text-slate-500 ml-1">Category</label>
                            <div class="relative">
                                <select name="category_id"
                                    class="w-full px-4 py-3 text-sm font-bold transition-all border outline-none appearance-none border-slate-200 bg-slate-50 rounded-xl focus:ring-2 focus:ring-emerald-500 text-slate-700">
                                    <option value="">-- Select Category --</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                <i
                                    class="absolute text-xs -translate-y-1/2 pointer-events-none right-4 top-1/2 fa-solid fa-chevron-down text-slate-400"></i>
                            </div>
                        </div>

                        {{-- Serial Number --}}
                        <div class="space-y-2">
                            <label class="text-[11px] font-black uppercase tracking-widest text-slate-500 ml-1">Serial
                                Number</label>
                            <input type="text" name="serial_number" placeholder="SN-XXXXX (Optional)"
                                class="w-full px-4 py-3 text-sm font-bold transition-all border outline-none border-slate-200 bg-slate-50 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:bg-white text-slate-700">
                        </div>

                        {{-- Type / Model --}}
                        <div class="space-y-2">
                            <label class="text-[11px] font-black uppercase tracking-widest text-slate-500 ml-1">Type /
                                Model</label>
                            <input type="text" name="type" required placeholder="Example: FS6000-X1"
                                class="w-full px-4 py-3 text-sm font-bold transition-all border outline-none border-slate-200 bg-slate-50 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:bg-white text-slate-700">
                        </div>

                        {{-- Qty & UOM --}}
                        <div class="space-y-2">
                            <label class="text-[11px] font-black uppercase tracking-widest text-slate-500 ml-1">Quantity &
                                UOM</label>
                            <div class="flex gap-0">
                                <input type="number" name="qty" required min="1" value="1"
                                    class="w-full px-4 py-3 text-sm font-bold transition-all border border-r-0 outline-none border-slate-200 bg-slate-50 rounded-l-xl focus:ring-2 focus:ring-emerald-500 focus:bg-white text-slate-700">
                                <select name="uom" required
                                    class="w-32 px-2 py-3 text-sm font-bold border outline-none border-slate-200 bg-slate-100 rounded-r-xl focus:ring-2 focus:ring-emerald-500 text-slate-700 border-l-slate-300">
                                    <option value="" disabled selected>Select Unit</option>

                                    <option value="PCS">PCS</option>
                                    <option value="SET">SET</option>
                                    <option value="UNIT">UNIT</option>
                                </select>
                            </div>
                        </div>

                        {{-- Condition --}}
                        <div class="space-y-2">
                            <label
                                class="text-[11px] font-black uppercase tracking-widest text-slate-500 ml-1">Condition</label>
                            <select name="condition" required
                                class="w-full px-4 py-3 text-sm font-bold transition-all border outline-none appearance-none border-slate-200 bg-slate-50 rounded-xl focus:ring-2 focus:ring-emerald-500 text-slate-700">
                                <option value="" disabled selected>Select Condition</option>
                                <option value="new">NEW</option>
                                <option value="used-good">USED (Good)</option>
                                <option value="damaged">DAMAGED</option>
                                <option value="repair">REPAIRED</option>
                            </select>
                        </div>
                    </div>

                    {{-- Image Upload --}}
                    {{-- <div class="space-y-2">
                        <label class="text-[11px] font-black uppercase tracking-widest text-slate-500 ml-1">Sparepart
                            Image</label>
                        <div class="flex items-center justify-center w-full">
                            <label
                                class="flex flex-col items-center justify-center w-full h-32 transition-colors border-2 border-dashed cursor-pointer rounded-2xl border-slate-200 bg-slate-50 hover:bg-slate-100">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                    <i class="mb-2 text-2xl fa-solid fa-cloud-arrow-up text-slate-400"></i>
                                    <p class="text-xs font-bold text-slate-500">Click to upload or drag and drop</p>
                                    <p class="text-[10px] text-slate-400 uppercase mt-1">PNG, JPG, WEBP (Max 2MB)</p>
                                </div>
                                <input type="file" name="image" class="hidden" accept="image/*" />
                            </label>
                        </div>
                    </div> --}}

                    {{-- Image Upload --}}
                    <div class="space-y-2">
                        <label class="text-[11px] font-black uppercase tracking-widest text-slate-500 ml-1">Sparepart
                            Image</label>
                        <div class="flex flex-col items-center justify-center w-full gap-4">
                            {{-- Area Upload --}}
                            <label id="edit-upload-label"
                                class="flex flex-col items-center justify-center w-full h-32 transition-colors border-2 border-dashed cursor-pointer rounded-2xl border-slate-200 bg-slate-50 hover:bg-slate-100">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                    <i class="mb-2 text-2xl fa-solid fa-cloud-arrow-up text-slate-400"></i>
                                    <p class="text-xs font-bold text-slate-500">Click to upload or drag and drop</p>
                                    <p class="text-[10px] text-slate-400 uppercase mt-1">PNG, JPG, WEBP (Max 2MB)</p>
                                </div>
                                <input type="file" name="image" id="edit-image-input" class="hidden"
                                    accept="image/*" onchange="previewEditImage(this)" />
                            </label>

                            {{-- Area Preview (Hidden by default) --}}
                            {{-- Area Preview (Hidden by default) --}}
                            <div id="edit-preview-container" class="relative hidden w-full group">
                                {{-- PERUBAHAN DI SINI: Ganti 'object-cover' menjadi 'object-contain' --}}
                                <img id="edit-image-preview" src="#" alt="Preview"
                                    class="object-contain w-full h-48 border-2 border-emerald-500 rounded-2xl bg-slate-50">

                                <button type="button" onclick="resetEditImage()"
                                    class="absolute flex items-center justify-center w-8 h-8 text-white transition-all bg-red-500 rounded-full shadow-lg top-2 right-2 hover:bg-red-600">
                                    <i class="text-xs fa-solid fa-trash"></i>
                                </button>
                                <div class="absolute bottom-2 left-2">
                                    <span
                                        class="px-3 py-1 text-[10px] font-bold text-white uppercase bg-emerald-600 rounded-lg shadow-sm">Image
                                        Selected</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Notes --}}
                    <div class="space-y-2">
                        <label class="text-[11px] font-black uppercase tracking-widest text-slate-500 ml-1">Notes</label>
                        <textarea name="note" rows="2" placeholder="Add additional information here..."
                            class="w-full px-4 py-3 text-sm font-bold transition-all border outline-none border-slate-200 bg-slate-50 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:bg-white text-slate-700"></textarea>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-end gap-3 px-8 py-6 border-t bg-slate-50/50 border-slate-100">
                    <button type="button" onclick="closeCreateModal()"
                        class="px-6 py-3 text-xs font-black tracking-widest uppercase transition-all text-slate-500 rounded-xl hover:bg-slate-100">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-8 py-3 text-xs font-black tracking-widest text-white uppercase transition-all shadow-lg bg-emerald-600 rounded-xl hover:bg-emerald-700 shadow-emerald-100">
                        <i class="mr-2 fa-solid fa-save"></i> Save Sparepart
                    </button>
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
        function previewEditImage(input) {
            const previewContainer = document.getElementById('edit-preview-container');
            const previewImage = document.getElementById('edit-image-preview');
            const uploadLabel = document.getElementById('edit-upload-label');

            if (input.files && input.files[0]) {
                const reader = new FileReader();

                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    previewContainer.classList.remove('hidden');
                    uploadLabel.classList.add('hidden');
                }

                reader.readAsDataURL(input.files[0]);
            }
        }

        function resetEditImage() {
            const input = document.getElementById('edit-image-input');
            const previewContainer = document.getElementById('edit-preview-container');
            const uploadLabel = document.getElementById('edit-upload-label');
            const previewImage = document.getElementById('edit-image-preview');

            input.value = "";
            previewImage.src = "#";
            previewContainer.classList.add('hidden');
            uploadLabel.classList.remove('hidden');
        }
    </script>

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
        class="fixed inset-0 z-50 flex items-center justify-center hidden px-4 transition-all duration-300 bg-slate-900/60 backdrop-blur-sm">
        <div class="relative w-full max-w-3xl overflow-hidden transition-all transform bg-white shadow-2xl rounded-3xl">
            {{-- Header --}}
            <div class="flex items-center justify-between px-8 py-6 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-12 h-12 text-blue-600 bg-blue-50 rounded-2xl">
                        <i class="text-xl fa-solid fa-pen-to-square"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-black tracking-tight text-slate-800">Edit Sparepart</h3>
                        <p class="text-xs font-bold tracking-wider uppercase text-slate-400">Inventory Management &bull;
                            Update Data</p>
                    </div>
                </div>
                <button onclick="closeEditModal()"
                    class="flex items-center justify-center w-10 h-10 transition-colors rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                    <i class="text-lg fa-solid fa-xmark"></i>
                </button>
            </div>

            <form id="form-edit" method="POST" enctype="multipart/form-data" class="max-h-[80vh] overflow-y-auto">
                @csrf
                @method('PUT')
                <div class="p-8 space-y-6">
                    {{-- Grid Utama --}}
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

                        {{-- Item Name --}}
                        <div class="space-y-2">
                            <label class="text-[11px] font-black uppercase tracking-widest text-slate-500 ml-1">Item
                                Name</label>
                            <input type="text" id="edit_item_name" name="item_name" required
                                placeholder="Example: Roller Conveyor"
                                class="w-full px-4 py-3 text-sm font-bold transition-all border outline-none border-slate-200 bg-slate-50 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white text-slate-700">
                        </div>

                        {{-- Category --}}
                        <div class="space-y-2">
                            <label
                                class="text-[11px] font-black uppercase tracking-widest text-slate-500 ml-1">Category</label>
                            <div class="relative">
                                <select id="edit_category_id" name="category_id"
                                    class="w-full px-4 py-3 text-sm font-bold transition-all border outline-none appearance-none border-slate-200 bg-slate-50 rounded-xl focus:ring-2 focus:ring-blue-500 text-slate-700">
                                    <option value="">-- Select Category --</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                <i
                                    class="absolute text-xs -translate-y-1/2 pointer-events-none right-4 top-1/2 fa-solid fa-chevron-down text-slate-400"></i>
                            </div>
                        </div>

                        {{-- Serial Number --}}
                        <div class="space-y-2">
                            <label class="text-[11px] font-black uppercase tracking-widest text-slate-500 ml-1">Serial
                                Number</label>
                            <input type="text" id="edit_serial_number" name="serial_number" placeholder="SN-XXXXX"
                                class="w-full px-4 py-3 text-sm font-bold transition-all border outline-none border-slate-200 bg-slate-50 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white text-slate-700">
                        </div>

                        {{-- Type / Model --}}
                        <div class="space-y-2">
                            <label class="text-[11px] font-black uppercase tracking-widest text-slate-500 ml-1">Type /
                                Model</label>
                            <input type="text" id="edit_type" name="type" required
                                placeholder="Example: FS6000-X1"
                                class="w-full px-4 py-3 text-sm font-bold transition-all border outline-none border-slate-200 bg-slate-50 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white text-slate-700">
                        </div>

                        {{-- Unit of Measure --}}
                        <div class="space-y-2">
                            <label class="text-[11px] font-black uppercase tracking-widest text-slate-500 ml-1">Unit of
                                Measure (UOM)</label>
                            <div class="relative">
                                <select id="edit_uom" name="uom" required
                                    class="w-full px-4 py-3 text-sm font-bold transition-all border outline-none appearance-none border-slate-200 bg-slate-50 rounded-xl focus:ring-2 focus:ring-blue-500 text-slate-700">
                                    <option value="PCS">PCS</option>
                                    <option value="SET">SET</option>
                                    <option value="UNIT">UNIT</option>
                                </select>
                                <i
                                    class="absolute text-xs -translate-y-1/2 pointer-events-none right-4 top-1/2 fa-solid fa-chevron-down text-slate-400"></i>
                            </div>
                        </div>

                        {{-- Note: Field Qty & Condition biasanya tidak diedit di modal sparepart utama 
                         karena menyangkut stok historis di database (sesuai controllermu), 
                         tapi jika ingin ditampilkan untuk info saja, bisa ditambahkan. --}}
                    </div>

                    {{-- Image Upload --}}
                    {{-- <div class="space-y-2">
                        <label class="text-[11px] font-black uppercase tracking-widest text-slate-500 ml-1">Update Image
                            (Optional)</label>
                        <div class="flex items-center justify-center w-full">
                            <label
                                class="flex flex-col items-center justify-center w-full h-32 transition-colors border-2 border-dashed cursor-pointer rounded-2xl border-slate-200 bg-slate-50 hover:bg-slate-100">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                    <i class="mb-2 text-2xl fa-solid fa-image text-slate-400"></i>
                                    <p class="text-xs font-bold text-slate-500">Click to replace current image</p>
                                    <p class="text-[10px] text-slate-400 uppercase mt-1">PNG, JPG, WEBP (Max 2MB)</p>
                                </div>
                                <input type="file" name="image" class="hidden" accept="image/*" />
                            </label>
                        </div>
                    </div> --}}
                    <div class="space-y-2">
                        <label class="text-[11px] font-black uppercase tracking-widest text-slate-500 ml-1">Sparepart
                            Image</label>
                        <div class="flex flex-col items-center justify-center w-full gap-4">
                            {{-- Area Upload --}}
                            <label id="upload-label"
                                class="flex flex-col items-center justify-center w-full h-32 transition-colors border-2 border-dashed cursor-pointer rounded-2xl border-slate-200 bg-slate-50 hover:bg-slate-100">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                    <i class="mb-2 text-2xl fa-solid fa-cloud-arrow-up text-slate-400"></i>
                                    <p class="text-xs font-bold text-slate-500">Click to upload or drag and drop</p>
                                    <p class="text-[10px] text-slate-400 uppercase mt-1">PNG, JPG, WEBP (Max 2MB)</p>
                                </div>
                                <input type="file" name="image" id="image-input" class="hidden" accept="image/*"
                                    onchange="previewImage(this)" />
                            </label>

                            {{-- Area Preview (Hidden by default) --}}
                            <div id="preview-container" class="relative hidden w-full group">
                                {{-- PERUBAHAN DI SINI: Ganti 'object-cover' menjadi 'object-contain' --}}
                                <img id="image-preview" src="#" alt="Preview"
                                    class="object-contain w-full h-48 border-2 border-emerald-500 rounded-2xl bg-slate-50">

                                <button type="button" onclick="resetImage()"
                                    class="absolute flex items-center justify-center w-8 h-8 text-white transition-all bg-red-500 rounded-full shadow-lg top-2 right-2 hover:bg-red-600">
                                    <i class="text-xs fa-solid fa-trash"></i>
                                </button>
                                <div class="absolute bottom-2 left-2">
                                    <span
                                        class="px-3 py-1 text-[10px] font-bold text-white uppercase bg-emerald-600 rounded-lg shadow-sm">Image
                                        Selected</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Notes --}}
                    <div class="space-y-2">
                        <label class="text-[11px] font-black uppercase tracking-widest text-slate-500 ml-1">Notes /
                            Description</label>
                        <textarea id="edit_note" name="note" rows="2" placeholder="Add additional information here..."
                            class="w-full px-4 py-3 text-sm font-bold transition-all border outline-none border-slate-200 bg-slate-50 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white text-slate-700"></textarea>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-end gap-3 px-8 py-6 border-t bg-slate-50/50 border-slate-100">
                    <button type="button" onclick="closeEditModal()"
                        class="px-6 py-3 text-xs font-black tracking-widest uppercase transition-all text-slate-500 rounded-xl hover:bg-slate-100">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-8 py-3 text-xs font-black tracking-widest text-white uppercase transition-all bg-blue-600 shadow-lg rounded-xl hover:bg-blue-700 shadow-blue-100">
                        <i class="mr-2 fa-solid fa-rotate"></i> Update Sparepart
                    </button>
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

    {{-- MODAL STOCK ADJUSTMENT --}}
    {{-- <div id="modal-adjustment"
        class="fixed inset-0 z-50 flex items-center justify-center hidden px-4 transition-all duration-300 bg-slate-900/60 backdrop-blur-sm">
        <div class="relative w-full max-w-2xl overflow-hidden transition-all transform bg-white shadow-2xl rounded-3xl">

            <div class="flex items-center justify-between px-8 py-6 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-12 h-12 text-amber-600 bg-amber-50 rounded-2xl">
                        <i class="text-xl fa-solid fa-sliders"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-black tracking-tight text-slate-800">Stock Adjustment</h3>
                        <p id="adj_item_display" class="text-xs italic font-bold tracking-wider uppercase text-slate-400">
                            Item Name Here</p>
                    </div>
                </div>
                <button onclick="closeAdjustmentModal()"
                    class="flex items-center justify-center w-10 h-10 transition-colors rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                    <i class="text-lg fa-solid fa-xmark"></i>
                </button>
            </div>

            <form id="form-adjustment" method="POST" class="p-8">
                @csrf
                <div class="space-y-6">

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div class="space-y-2">
                            <label class="text-[11px] font-black uppercase tracking-widest text-slate-400 ml-1">Current
                                Stock</label>
                            <input type="text" id="adj_current_qty" readonly
                                class="w-full px-4 py-3 text-sm font-black border-none cursor-not-allowed bg-slate-100 rounded-xl text-slate-500">
                        </div>

                        <div class="space-y-2">
                            <label
                                class="text-[11px] font-black uppercase tracking-widest text-slate-400 ml-1">Condition</label>
                            <input type="text" id="adj_condition_display" readonly
                                class="w-full px-4 py-3 text-sm font-black uppercase border-none cursor-not-allowed bg-slate-100 rounded-xl text-slate-500">
                            <input type="hidden" name="condition" id="adj_condition_value">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div class="space-y-2">
                            <label class="text-[11px] font-black uppercase tracking-widest text-slate-500 ml-1">
                                Action Type
                            </label>
                            <div class="relative group">
                                <select name="action" required
                                    class="w-full px-4 py-3 pr-10 text-sm font-bold transition-all border outline-none appearance-none cursor-pointer border-slate-200 bg-slate-50 rounded-xl focus:ring-2 focus:ring-amber-500 focus:bg-white text-slate-700">
                                    <option value="" disabled selected>Choose Action...</option>
                                    <option value="IN">Stock In (Add)</option>
                                    <option value="OUT">Stock Out (Reduce)</option>
                                    <option value="ADJUST">Correction (Set Value)</option>
                                </select>

                                <div
                                    class="absolute inset-y-0 right-0 flex items-center pr-4 transition-colors pointer-events-none text-slate-400 group-focus-within:text-amber-500">
                                    <i class="text-xs fa-solid fa-chevron-down"></i>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-2 ">
                            <label class="text-[11px] font-black uppercase tracking-widest text-slate-500 ml-1">Adjustment
                                Qty</label>
                            <input type="number" name="qty" required min="1" placeholder="Enter amount..."
                                class="w-full px-4 py-3 text-sm font-bold transition-all border outline-none border-slate-200 bg-slate-50 rounded-xl focus:ring-2 focus:ring-amber-500 focus:bg-white text-slate-700">
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[11px] font-black uppercase tracking-widest text-slate-500 ml-1">Reason for
                            Adjustment</label>
                        <textarea name="note" required rows="3"
                            placeholder="e.g., Damaged during transit, Yearly audit correction..."
                            class="w-full px-4 py-3 text-sm font-bold transition-all border outline-none border-slate-200 bg-slate-50 rounded-xl focus:ring-2 focus:ring-amber-500 focus:bg-white text-slate-700"></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-6 mt-8 border-t border-slate-100">
                    <button type="button" onclick="closeAdjustmentModal()"
                        class="px-6 py-3 text-xs font-black tracking-widest uppercase transition-all text-slate-500 rounded-xl hover:bg-slate-100">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-8 py-3 text-xs font-black tracking-widest text-white uppercase transition-all shadow-lg bg-amber-600 rounded-xl hover:bg-amber-700 shadow-amber-100">
                        <i class="mr-2 fa-solid fa-check-double"></i> Process Adjustment
                    </button>
                </div>
            </form>
        </div>
    </div> --}}
    {{-- MODAL STOCK ADJUSTMENT (SPLIT) --}}
    <div id="modal-adjust"
        class="fixed inset-0 z-50 flex items-center justify-center hidden px-4 bg-slate-900/60 backdrop-blur-sm">
        <div class="relative w-full max-w-lg overflow-hidden bg-white shadow-2xl rounded-3xl">
            <div class="px-8 py-6 border-b border-slate-100">
                <h3 class="text-xl font-black text-slate-800">Stock Adjustment</h3>
                <p id="adjust-item-name" class="text-sm font-bold text-blue-600 uppercase"></p>
            </div>

            <form id="form-adjust" method="POST">
                @csrf
                <input type="hidden" name="current_condition" id="input-current-condition">
                <div class="p-8 space-y-5">
                    {{-- Info Stok Saat Ini --}}
                    <div class="p-4 border bg-slate-50 rounded-2xl border-slate-100">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Current Stock</p>
                        <p id="current-stock-display" class="text-2xl font-black text-slate-700"></p>
                    </div>

                    {{-- Pilihan Mode --}}
                    <div class="grid grid-cols-2 gap-3 p-1 bg-slate-100 rounded-2xl">
                        <button type="button" onclick="setAdjustMode('update')" id="btn-mode-update"
                            class="py-2 text-xs font-black transition-all bg-white shadow-sm rounded-xl text-slate-700">UPDATE
                            TOTAL</button>
                        <button type="button" onclick="setAdjustMode('split')" id="btn-mode-split"
                            class="py-2 text-xs font-black transition-all rounded-xl text-slate-500 hover:text-slate-700">SPLIT
                            CONDITION</button>
                    </div>
                    <input type="hidden" name="adjustment_type" id="input-adjust-type" value="update">

                    <div class="grid grid-cols-2 gap-4">
                        {{-- Input Qty --}}
                        <div class="space-y-2">
                            <label id="label-qty" class="text-[11px] font-black uppercase text-slate-500 ml-1">New Total
                                Qty</label>
                            <input type="number" name="qty_to_move" id="input-qty-adjust" required
                                class="w-full px-4 py-3 text-sm font-bold border outline-none border-slate-200 bg-slate-50 rounded-xl focus:ring-2 focus:ring-blue-500">
                        </div>

                        {{-- Target Condition --}}
                        <div class="space-y-2">
                            <label class="text-[11px] font-black uppercase text-slate-500 ml-1">Condition</label>
                            <select name="new_condition" id="select-condition-adjust"
                                class="w-full px-4 py-3 text-sm font-bold border outline-none border-slate-200 bg-slate-50 rounded-xl focus:ring-2 focus:ring-blue-500">
                                <option value="new">NEW</option>
                                <option value="used-good">USED (Good)</option>
                                <option value="damaged">DAMAGED</option>
                                <option value="repair">REPAIRED</option>
                            </select>
                        </div>
                    </div>

                    <p id="split-hint" class="hidden text-[11px] italic text-orange-600 font-medium leading-tight">
                        * This will subtract X from current stock and create a new row with the selected condition.
                    </p>
                </div>

                <div class="flex items-center justify-end gap-3 px-8 py-6 border-t bg-slate-50/50 border-slate-100">
                    <button type="button" onclick="closeAdjustModal()"
                        class="text-xs font-black uppercase text-slate-400 hover:text-slate-600">Cancel</button>
                    <button type="submit"
                        class="px-8 py-3 text-xs font-black text-white uppercase bg-blue-600 shadow-lg rounded-xl hover:bg-blue-700 shadow-blue-200">
                        Process Adjustment
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function previewImage(input) {
            const previewContainer = document.getElementById('preview-container');
            const previewImage = document.getElementById('image-preview');
            const uploadLabel = document.getElementById('upload-label');

            if (input.files && input.files[0]) {
                const reader = new FileReader();

                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    previewContainer.classList.remove('hidden');
                    uploadLabel.classList.add('hidden'); // Sembunyikan tombol upload agar rapi
                }

                reader.readAsDataURL(input.files[0]);
            }
        }

        function resetImage() {
            const input = document.getElementById('image-input');
            const previewContainer = document.getElementById('preview-container');
            const uploadLabel = document.getElementById('upload-label');
            const previewImage = document.getElementById('image-preview');

            input.value = ""; // Reset input file
            previewImage.src = "#";
            previewContainer.classList.add('hidden');
            uploadLabel.classList.remove('hidden');
        }

        // Pastikan reset dipanggil saat modal ditutup agar gambar lama tidak nyangkut
        function closeCreateModal() {
            document.getElementById('modal-create').classList.add('hidden');
            resetImage();
        }
        /**
         * Membuka Modal Adjustment dan mengisi data awal
         */
        function openAdjustmentModal(data) {
            const modal = document.getElementById('modal-adjustment');
            const form = document.getElementById('form-adjustment');

            // Reset form agar input qty dan note dari sesi sebelumnya hilang
            form.reset();

            // Set Action URL (Sesuaikan dengan route Laravel kamu)
            form.action = `/sparepart/adjustment/${data.slug}/${data.id}`;

            // Binding Data ke UI
            document.getElementById('adj_item_display').innerText = data.item_name;
            document.getElementById('adj_current_qty').value = `${data.qty} ${data.uom}`;
            document.getElementById('adj_condition_display').value = data.condition.toUpperCase();
            document.getElementById('adj_condition_value').value = data.condition;

            // Tampilkan Modal dengan menghapus class 'hidden'
            modal.classList.remove('hidden');

            // Opsional: Tambahkan sedikit delay untuk animasi smooth jika diperlukan
            modal.style.opacity = "1";
        }

        /**
         * Menutup Modal Adjustment
         */
        function closeAdjustmentModal() {
            const modal = document.getElementById('modal-adjustment');

            // Sembunyikan Modal
            modal.classList.add('hidden');
        }

        // Tambahan: Menutup modal jika user mengklik area di luar box putih (backdrop)
        window.onclick = function(event) {
            const modal = document.getElementById('modal-adjustment');
            if (event.target == modal) {
                closeAdjustmentModal();
            }
        }
    </script>

    <script>
        // function openAdjustModal(id, name, qty, condition) {
        //     const modal = document.getElementById('modal-adjust');
        //     const form = document.getElementById('form-adjust');

        //     // Set data awal
        //     document.getElementById('adjust-item-name').innerText = name;
        //     document.getElementById('current-stock-display').innerText = `${qty} Units`;
        //     document.getElementById('input-qty-adjust').value = qty;
        //     document.getElementById('select-condition-adjust').value = condition;

        //     // Set route (sesuaikan dengan route Laravel anda)
        //     form.action = `/spareparts/adjust/${id}`;

        //     modal.classList.remove('hidden');
        // }
        function openAdjustModal(id, name, qty, condition) {
            const modal = document.getElementById('modal-adjust');
            const form = document.getElementById('form-adjust');
            const slug = "{{ $slug }}";

            // Pastikan URL diawali dengan slash / agar tidak relatif
            form.action = `/inventory/${slug}/adjust/${id}`;

            // Isi data ke UI
            document.getElementById('adjust-item-name').innerText = name;
            document.getElementById('current-stock-display').innerText = qty;

            // Simpan kondisi saat ini ke input hidden agar terkirim ke Controller
            document.getElementById('input-current-condition').value = condition;

            // Default value untuk input qty
            document.getElementById('input-qty-adjust').value = qty;

            modal.classList.remove('hidden');
        }

        function setAdjustMode(mode) {
            const inputType = document.getElementById('input-adjust-type');
            const labelQty = document.getElementById('label-qty');
            const hint = document.getElementById('split-hint');
            const btnUpdate = document.getElementById('btn-mode-update');
            const btnSplit = document.getElementById('btn-mode-split');

            inputType.value = mode;

            if (mode === 'split') {
                labelQty.innerText = "Qty to Split";
                hint.classList.remove('hidden');
                // UI Toggle
                btnSplit.classList.add('bg-white', 'shadow-sm', 'text-slate-700');
                btnSplit.classList.remove('text-slate-500');
                btnUpdate.classList.remove('bg-white', 'shadow-sm', 'text-slate-700');
                btnUpdate.classList.add('text-slate-500');
            } else {
                labelQty.innerText = "New Total Qty";
                hint.classList.add('hidden');
                // UI Toggle
                btnUpdate.classList.add('bg-white', 'shadow-sm', 'text-slate-700');
                btnUpdate.classList.remove('text-slate-500');
                btnSplit.classList.remove('bg-white', 'shadow-sm', 'text-slate-700');
                btnSplit.classList.add('text-slate-500');
            }
        }

        function closeAdjustModal() {
            document.getElementById('modal-adjust').classList.add('hidden');
        }
    </script>
@endsection
