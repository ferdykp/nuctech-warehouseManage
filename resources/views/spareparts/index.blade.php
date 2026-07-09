@extends('layout.master')

@section('content')
    <div class="w-full px-6 py-8">
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

                {{-- Quick Stats Mini --}}
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
                $pendingApprovals = \App\Models\SparepartTransfer::where('from_site_id', $siteData->id)
                    ->where('status', 'pending')
                    ->with('sparepart')
                    ->get();

                $pendingReceipts = \App\Models\SparepartTransfer::where('to_site_id', $siteData->id)
                    ->where('status', 'approved')
                    ->with('sparepart')
                    ->get();
            @endphp

            @if ($pendingApprovals->count() > 0 || $pendingReceipts->count() > 0)
                <div class="px-8 py-6 space-y-3 bg-white border-b border-slate-100">
                    <h4 class="mb-2 text-xs font-black tracking-widest uppercase text-slate-400">Attention Required</h4>

                    {{-- Section APPROVAL --}}
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
                                        Destination: {{ $t->toSite->machine_name }} • Qty: {{ $t->qty }}
                                        {{ $t->sparepart->uom }} •
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

                    {{-- Section RECEIPT --}}
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
                                            From: {{ $t->fromSite->machine_name }} • Incoming Condition:
                                            <span class="font-bold text-blue-800">{{ strtoupper($t->condition) }}</span>
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

                        <div class="relative w-full md:w-80">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input type="text" name="search" id="search"
                                placeholder="Type to search name or Serial..." value="{{ request('search') }}"
                                class="block w-full py-2.5 pl-10 pr-3 text-sm border-gray-200 rounded-xl bg-white focus:border-blue-500 focus:ring-blue-500 transition-all outline-none border shadow-sm">
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
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div class="space-y-2">
                            <label class="text-[11px] font-black uppercase tracking-widest text-slate-500 ml-1">Item
                                Name</label>
                            <input type="text" name="item_name" required placeholder="Example: Roller Conveyor"
                                class="w-full px-4 py-3 text-sm font-bold transition-all border outline-none border-slate-200 bg-slate-50 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:bg-white text-slate-700">
                        </div>

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

                        <div class="space-y-2">
                            <label class="text-[11px] font-black uppercase tracking-widest text-slate-500 ml-1">Serial
                                Number</label>
                            <input type="text" name="serial_number" placeholder="SN-XXXXX (Optional)"
                                class="w-full px-4 py-3 text-sm font-bold transition-all border outline-none border-slate-200 bg-slate-50 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:bg-white text-slate-700">
                        </div>

                        <div class="space-y-2">
                            <label class="text-[11px] font-black uppercase tracking-widest text-slate-500 ml-1">Type /
                                Model</label>
                            <input type="text" name="type" required placeholder="Example: FS6000-X1"
                                class="w-full px-4 py-3 text-sm font-bold transition-all border outline-none border-slate-200 bg-slate-50 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:bg-white text-slate-700">
                        </div>

                        <div class="space-y-2">
                            <label class="text-[11px] font-black uppercase tracking-widest text-slate-500 ml-1">Quantity &
                                UOM</label>
                            <div class="flex gap-0">
                                <input type="number" name="qty" required min="1" value="1"
                                    class="w-full px-4 py-3 text-sm font-bold transition-all border border-r-0 outline-none border-slate-200 bg-slate-50 rounded-l-xl focus:ring-2 focus:ring-emerald-500 focus:bg-white text-slate-700">
                                <select name="uom" required
                                    class="w-32 px-2 py-3 text-sm font-bold border outline-none border-slate-200 bg-slate-100 rounded-r-xl focus:ring-2 focus:ring-emerald-500 text-slate-700 border-l-slate-300">
                                    <option value="" disabled selected>Unit</option>
                                    <option value="PCS">PCS</option>
                                    <option value="SET">SET</option>
                                    <option value="UNIT">UNIT</option>
                                </select>
                            </div>
                        </div>

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

                    <div class="space-y-2">
                        <label class="text-[11px] font-black uppercase tracking-widest text-slate-500 ml-1">Sparepart
                            Image</label>
                        <div class="flex flex-col items-center justify-center w-full gap-4">
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

                            <div id="edit-preview-container" class="relative hidden w-full group">
                                <img id="edit-image-preview" src="#" alt="Preview"
                                    class="object-contain w-full h-48 border-2 border-emerald-500 rounded-2xl bg-slate-50">
                                <button type="button" onclick="resetEditImage()"
                                    class="absolute flex items-center justify-center w-8 h-8 text-white transition-all bg-red-500 rounded-full shadow-lg top-2 right-2 hover:bg-red-600">
                                    <i class="text-xs fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[11px] font-black uppercase tracking-widest text-slate-500 ml-1">Notes</label>
                        <textarea name="note" rows="2" placeholder="Add additional information here..."
                            class="w-full px-4 py-3 text-sm font-bold transition-all border outline-none border-slate-200 bg-slate-50 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:bg-white text-slate-700"></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 px-8 py-6 border-t bg-slate-50/50 border-slate-100">
                    <button type="button" onclick="closeCreateModal()"
                        class="px-6 py-3 text-xs font-black tracking-widest uppercase transition-all text-slate-500 rounded-xl hover:bg-slate-100">Cancel</button>
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
                <div id="import-dropzone"
                    class="flex flex-col items-center justify-center w-full h-32 transition-all border-2 border-gray-300 border-dashed cursor-pointer rounded-xl hover:border-blue-400 hover:bg-blue-50/50">
                    <i id="dropzone-icon" class="mb-2 text-3xl text-gray-400 fa-solid fa-cloud-arrow-up"></i>
                    <span id="dropzone-text" class="text-sm font-medium text-gray-500">Click or drag file here</span>
                    <span id="dropzone-hint" class="mt-1 text-xs text-gray-400">.xlsx / .xls / .csv — max 10MB</span>
                    <input type="file" id="import-file-input" name="file" class="hidden"
                        accept=".xlsx,.xls,.csv">
                </div>
                <button type="submit" id="btn-submit-import"
                    class="hidden w-full px-5 py-3 mt-4 text-sm font-bold text-white transition-all bg-blue-600 shadow-lg rounded-xl hover:bg-blue-700 shadow-blue-100">
                    <i class="mr-2 fa-solid fa-file-import"></i> Submit & Import Data
                </button>
            </form>
        </div>
    </div>

    {{-- TOAST CONTAINER --}}
    <div id="import-toast-container" class="fixed z-[60] top-5 right-5 space-y-3"></div>

    {{-- MODAL EDIT --}}
    <div id="modal-edit"
        class="fixed inset-0 z-50 flex items-center justify-center hidden px-4 transition-all duration-300 bg-slate-900/60 backdrop-blur-sm">
        <div class="relative w-full max-w-3xl overflow-hidden transition-all transform bg-white shadow-2xl rounded-3xl">
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
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div class="space-y-2">
                            <label class="text-[11px] font-black uppercase tracking-widest text-slate-500 ml-1">Item
                                Name</label>
                            <input type="text" id="edit_item_name" name="item_name" required
                                class="w-full px-4 py-3 text-sm font-bold transition-all border outline-none border-slate-200 bg-slate-50 rounded-xl focus:ring-2 focus:ring-blue-500 text-slate-700">
                        </div>
                        <div class="space-y-2">
                            <label
                                class="text-[11px] font-black uppercase tracking-widest text-slate-500 ml-1">Category</label>
                            <select id="edit_category_id" name="category_id"
                                class="w-full px-4 py-3 text-sm font-bold transition-all border outline-none border-slate-200 bg-slate-50 rounded-xl focus:ring-2 focus:ring-blue-500 text-slate-700">
                                <option value="">-- Select Category --</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label class="text-[11px] font-black uppercase tracking-widest text-slate-500 ml-1">Serial
                                Number</label>
                            <input type="text" id="edit_serial_number" name="serial_number"
                                class="w-full px-4 py-3 text-sm font-bold transition-all border outline-none border-slate-200 bg-slate-50 rounded-xl focus:ring-2 focus:ring-blue-500 text-slate-700">
                        </div>
                        <div class="space-y-2">
                            <label class="text-[11px] font-black uppercase tracking-widest text-slate-500 ml-1">Type /
                                Model</label>
                            <input type="text" id="edit_type" name="type" required
                                class="w-full px-4 py-3 text-sm font-bold transition-all border outline-none border-slate-200 bg-slate-50 rounded-xl focus:ring-2 focus:ring-blue-500 text-slate-700">
                        </div>
                        <div class="space-y-2">
                            <label class="text-[11px] font-black uppercase tracking-widest text-slate-500 ml-1">UOM</label>
                            <select id="edit_uom" name="uom" required
                                class="w-full px-4 py-3 text-sm font-bold transition-all border outline-none border-slate-200 bg-slate-50 rounded-xl focus:ring-2 focus:ring-blue-500 text-slate-700">
                                <option value="PCS">PCS</option>
                                <option value="SET">SET</option>
                                <option value="UNIT">UNIT</option>
                            </select>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[11px] font-black uppercase tracking-widest text-slate-500 ml-1">Sparepart
                            Image</label>
                        <div class="flex flex-col items-center justify-center w-full gap-4">
                            <label id="upload-label"
                                class="flex flex-col items-center justify-center w-full h-32 transition-colors border-2 border-dashed cursor-pointer rounded-2xl border-slate-200 bg-slate-50 hover:bg-slate-100">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                    <i class="mb-2 text-2xl fa-solid fa-cloud-arrow-up text-slate-400"></i>
                                    <p class="text-xs font-bold text-slate-500">Click to upload or drag and drop</p>
                                </div>
                                <input type="file" name="image" id="image-input" class="hidden" accept="image/*"
                                    onchange="previewImage(this)" />
                            </label>
                            <div id="preview-container" class="relative hidden w-full group">
                                <img id="image-preview" src="#" alt="Preview"
                                    class="object-contain w-full h-48 border-2 border-emerald-500 rounded-2xl bg-slate-50">
                                <button type="button" onclick="resetImage()"
                                    class="absolute flex items-center justify-center w-8 h-8 text-white bg-red-500 rounded-full top-2 right-2"><i
                                        class="fa-solid fa-trash"></i></button>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[11px] font-black uppercase tracking-widest text-slate-500 ml-1">Notes /
                            Description</label>
                        <textarea id="edit_note" name="note" rows="2"
                            class="w-full px-4 py-3 text-sm font-bold border outline-none border-slate-200 bg-slate-50 rounded-xl focus:ring-2 focus:ring-blue-500 text-slate-700"></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 px-8 py-6 border-t bg-slate-50/50 border-slate-100">
                    <button type="button" onclick="closeEditModal()"
                        class="px-6 py-3 text-xs font-black tracking-widest text-slate-500 rounded-xl hover:bg-slate-100">Cancel</button>
                    <button type="submit"
                        class="px-8 py-3 text-xs font-black text-white bg-blue-600 rounded-xl hover:bg-blue-700">Update
                        Sparepart</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL ADJUSTMENT STOK TERPADU (UI/UX DISEDERHANAKAN) --}}
    <div id="modal-adjust"
        class="fixed inset-0 z-50 flex items-center justify-center hidden px-4 bg-slate-900/60 backdrop-blur-sm">
        <div class="relative w-full max-w-md overflow-hidden bg-white shadow-2xl rounded-3xl">
            <div class="px-8 py-6 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 text-blue-600 rounded-xl bg-blue-50">
                        <i class="text-sm fa-solid fa-sliders"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-slate-800">Ubah Status / Stok</h3>
                        <p id="adjust-item-name"
                            class="text-xs font-bold text-slate-400 uppercase truncate max-w-[240px]"></p>
                    </div>
                </div>
            </div>

            <form id="form-adjust" method="POST">
                @csrf
                <input type="hidden" name="current_condition" id="input-current-condition">
                <div class="p-8 space-y-4">
                    <div class="grid grid-cols-2 gap-3 p-3.5 bg-slate-50 border border-slate-100 rounded-2xl text-center">
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Kondisi Saat Ini</p>
                            <span id="display-current-condition"
                                class="inline-block mt-1 px-2.5 py-0.5 rounded-lg text-xs font-black bg-slate-200 text-slate-700 uppercase"></span>
                        </div>
                        <div class="border-l border-slate-200">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Stok Tersedia</p>
                            <p id="current-stock-display" class="mt-1 text-base font-black text-slate-700"></p>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-[11px] font-black uppercase text-slate-500 ml-1">Jumlah Unit yang Diubah</label>
                        <div class="relative">
                            <input type="number" name="qty_to_move" id="input-qty-adjust" required min="1"
                                class="w-full px-4 py-3 text-sm font-bold transition-all bg-white border outline-none border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 text-slate-700">
                            <span id="adjust-uom-badge"
                                class="absolute right-4 top-3.5 text-xs font-bold text-slate-400 uppercase">Units</span>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-[11px] font-black uppercase text-slate-500 ml-1">Target Kondisi Baru</label>
                        <div class="relative">
                            <select name="new_condition" id="select-condition-adjust" required
                                class="w-full px-4 py-3 text-sm font-bold transition-all bg-white border outline-none appearance-none border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 text-slate-700">
                                <option value="new">NEW</option>
                                <option value="used-good">USED (Good)</option>
                                <option value="damaged">DAMAGED (Rusak)</option>
                                <option value="repair">REPAIRED</option>
                            </select>
                            <i
                                class="fa-solid fa-chevron-down absolute right-4 top-4 text-slate-400 pointer-events-none text-[10px]"></i>
                        </div>
                    </div>

                    <div id="adjustment-hint-box"
                        class="p-3 bg-blue-50 border border-blue-100 rounded-xl text-[11px] text-blue-700 leading-normal flex gap-2">
                        <i class="fa-solid fa-circle-info mt-0.5 shrink-0"></i>
                        <span id="adjustment-hint-text">Ketikkan jumlah unit untuk melihat aksi kalkulasi.</span>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 px-8 py-5 border-t bg-slate-50/50 border-slate-100">
                    <button type="button" onclick="closeAdjustModal()"
                        class="text-xs font-black uppercase text-slate-400 hover:text-slate-600">Batal</button>
                    <button type="submit" id="btn-submit-adjust"
                        class="px-6 py-2.5 text-xs font-black text-white uppercase bg-blue-600 shadow-md shadow-blue-100 rounded-xl hover:bg-blue-700">Simpan
                        Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(btn) {
            // Ambil data JSON dari tombol
            const item = JSON.parse(btn.getAttribute('data-item'));
            const modal = document.getElementById('modal-edit');
            const form = document.getElementById('form-edit');

            // Mengatasi jika data yang dikirim adalah object Stock (punya relasi 'sparepart')
            // atau jika object Sparepart biasa.
            const sparepartId = item.sparepart_id ? item.sparepart_id : item.id;
            const itemName = item.sparepart ? item.sparepart.item_name : item.item_name;
            const categoryId = item.sparepart ? item.sparepart.category_id : item.category_id;
            const serialNumber = item.sparepart ? item.sparepart.serial_number : item.serial_number;
            const type = item.sparepart ? item.sparepart.type : item.type;
            const uom = item.sparepart ? item.sparepart.uom : item.uom;
            const note = item.sparepart ? item.sparepart.note : item.note;

            // Set Action URL Form Update (Mengarahkan ke ID Sparepart)
            form.action = `/sparepart/{{ $slug }}/${sparepartId}`;

            // Isi nilai ke field input modal edit
            document.getElementById('edit_item_name').value = itemName || '';
            document.getElementById('edit_category_id').value = categoryId || '';
            document.getElementById('edit_serial_number').value = serialNumber || '';
            document.getElementById('edit_type').value = type || '';
            document.getElementById('edit_uom').value = uom || 'PCS';
            document.getElementById('edit_note').value = note || '';

            // Tampilkan modal edit
            modal.classList.remove('hidden');
        }
        // --- PREVIEW SCRIPT MANAGEMENT ---
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

        function previewImage(input) {
            const previewContainer = document.getElementById('preview-container');
            const previewImage = document.getElementById('image-preview');
            const uploadLabel = document.getElementById('upload-label');
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

        function resetImage() {
            const input = document.getElementById('image-input');
            const previewContainer = document.getElementById('preview-container');
            const uploadLabel = document.getElementById('upload-label');
            const previewImage = document.getElementById('image-preview');
            input.value = "";
            previewImage.src = "#";
            previewContainer.classList.add('hidden');
            uploadLabel.classList.remove('hidden');
        }

        function closeCreateModal() {
            document.getElementById('modal-create').classList.add('hidden');
            resetImage();
        }

        function openCreateModal() {
            document.getElementById('modal-create').classList.remove('hidden');
        }

        function openImportModal() {
            const modal = document.getElementById('modal-import');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeImportModal() {
            document.getElementById('modal-import').classList.add('hidden');
        }

        // --- EXCEL DRAG & DROP ---
        const dropzone = document.getElementById('import-dropzone');
        const fileInput = document.getElementById('import-file-input');
        if (dropzone) {
            dropzone.addEventListener('click', () => fileInput.click());
            dropzone.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropzone.classList.add('bg-blue-50');
            });
            dropzone.addEventListener('dragleave', () => dropzone.classList.remove('bg-blue-50'));
            dropzone.addEventListener('drop', (e) => {
                e.preventDefault();
                dropzone.classList.remove('bg-blue-50');
                if (e.dataTransfer.files.length) {
                    fileInput.files = e.dataTransfer.files;
                    fileInput.dispatchEvent(new Event('change'));
                }
            });
        }

        if (fileInput) {
            fileInput.addEventListener('change', function() {
                if (!this.files.length) return;
                document.getElementById('dropzone-text').textContent = this.files[0].name;
                document.getElementById('btn-submit-import').classList.remove('hidden');
            });
        }
    </script>

    <script>
        // --- JAVASCRIPT UX ENGINE FOR ADJUSTMENT (TERPADU & AUTOMATIC) ---
        let maxAvailableStock = 0;
        let currentActiveCondition = '';

        function openAdjustModal(id, name, qty, condition, uom = 'Units') {
            const modal = document.getElementById('modal-adjust');
            const form = document.getElementById('form-adjust');
            const slug = "{{ $slug }}";

            maxAvailableStock = parseInt(qty);
            currentActiveCondition = condition;

            // Targetkan route langsung menggunakan primary key row stock ($id)
            form.action = `/inventory/${slug}/adjust/${id}`;

            document.getElementById('adjust-item-name').innerText = name;
            document.getElementById('current-stock-display').innerText = `${qty} ${uom}`;
            document.getElementById('adjust-uom-badge').innerText = uom;
            document.getElementById('display-current-condition').innerText = condition.replace('-', ' ');
            document.getElementById('input-current-condition').value = condition;

            const qtyInput = document.getElementById('input-qty-adjust');
            qtyInput.value = qty;
            qtyInput.max = qty;

            document.getElementById('select-condition-adjust').value = condition;

            calculateLiveHint();
            modal.classList.remove('hidden');
        }

        function closeAdjustModal() {
            document.getElementById('modal-adjust').classList.add('hidden');
        }

        const qtyInput = document.getElementById('input-qty-adjust');
        const conditionSelect = document.getElementById('select-condition-adjust');
        const hintBox = document.getElementById('adjustment-hint-box');
        const hintText = document.getElementById('adjustment-hint-text');
        const btnSubmit = document.getElementById('btn-submit-adjust');

        function calculateLiveHint() {
            const enteredQty = parseInt(qtyInput.value) || 0;
            const selectedCondition = conditionSelect.value;

            if (enteredQty <= 0) {
                hintBox.className = "p-3 bg-red-50 border border-red-100 rounded-xl text-[11px] text-red-700 flex gap-2";
                hintText.innerHTML = "Jumlah unit pengubahan minimal harus 1 unit.";
                btnSubmit.disabled = true;
                return;
            }

            if (enteredQty > maxAvailableStock) {
                hintBox.className = "p-3 bg-red-50 border border-red-100 rounded-xl text-[11px] text-red-700 flex gap-2";
                hintText.innerHTML =
                    `Jumlah input melebihi batas kuantitas stok tersedia (Maksimal: ${maxAvailableStock} unit).`;
                btnSubmit.disabled = true;
                return;
            }

            btnSubmit.disabled = false;

            if (currentActiveCondition === selectedCondition) {
                hintBox.className = "p-3 bg-blue-50 border border-blue-100 rounded-xl text-[11px] text-blue-700 flex gap-2";
                hintText.innerHTML =
                    `<b>Koreksi Jumlah Stok:</b> Angka total stok pada kondisi ini akan langsung disesuaikan menjadi <b>${enteredQty} unit</b>.`;
            } else if (enteredQty === maxAvailableStock) {
                hintBox.className =
                    "p-3 bg-emerald-50 border border-emerald-100 rounded-xl text-[11px] text-emerald-700 flex gap-2";
                hintText.innerHTML =
                    `<b>Mutasi Kondisi Total:</b> Semua stok (${enteredQty} unit) akan dipindahkan statusnya secara penuh ke kondisi <b>${selectedCondition.toUpperCase()}</b>.`;
            } else {
                hintBox.className =
                    "p-3 bg-amber-50 border border-amber-100 rounded-xl text-[11px] text-amber-700 flex gap-2";
                hintText.innerHTML =
                    `<b>Split Kondisi Sebagian:</b> Sebanyak <b>${enteredQty} unit</b> dipisah ke status <b>${selectedCondition.toUpperCase()}</b>, menyisakan <b>${maxAvailableStock - enteredQty} unit</b> pada status lama.`;
            }
        }

        qtyInput.addEventListener('input', calculateLiveHint);
        conditionSelect.addEventListener('change', calculateLiveHint);
    </script>

    <script>
        // --- LIVE SEARCH SCRIPT ---
        const searchInput = document.getElementById('search');
        const tableContainer = document.getElementById('table-container');
        let delayTimer;

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(delayTimer);
                delayTimer = setTimeout(() => {
                    const query = searchInput.value;
                    fetch(`{{ route('sparepart.index', $siteData->slug) }}?search=${encodeURIComponent(query)}`, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(response => response.text())
                        .then(html => {
                            tableContainer.innerHTML = html;
                        })
                        .catch(error => console.error('Error Live Search:', error));
                }, 300);
            });
        }
    </script>
@endsection
