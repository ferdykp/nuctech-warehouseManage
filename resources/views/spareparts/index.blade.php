@extends('layout.master')

@section('title', 'Site Sparepart Inventory')

@section('content')
    <div class="w-full space-y-6">

        {{-- 1. HEADER CARD --}}
        <div class="p-6 bg-white border shadow-xs sm:p-8 border-slate-200/80 rounded-3xl">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <div
                        class="inline-flex items-center gap-2 px-3 py-1 mb-2 text-xs font-bold text-blue-700 border border-blue-100 rounded-full bg-blue-50">
                        <i class="fa-solid fa-industry text-[10px]"></i> Site Machine Unit
                    </div>
                    <h1 class="text-2xl font-extrabold tracking-tight uppercase sm:text-3xl text-slate-900">
                        {{ $siteData->machine_name }}
                    </h1>
                    <p class="flex items-center gap-2 mt-1 text-xs font-semibold sm:text-sm text-slate-500">
                        <span><i
                                class="mr-1 fa-solid fa-location-dot text-slate-400"></i>{{ $siteData->branch->branch_name ?? 'Unassigned Branch' }}</span>
                        <span class="text-slate-300">•</span>
                        <span>Site Inventory Monitor</span>
                    </p>
                </div>

                {{-- Quick Stats Mini --}}
                <div
                    class="flex items-center gap-6 pt-4 border-t md:pt-0 md:border-t-0 md:border-l border-slate-200 md:pl-8">
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Total SKU Items</p>
                        <p class="text-2xl font-black text-slate-800">{{ $data->total() }}</p>
                    </div>
                    <div class="w-px h-8 bg-slate-200"></div>
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Status Node</p>
                        <p class="flex items-center mt-1 text-xs font-bold text-emerald-600">
                            <span class="w-2 h-2 mr-2 rounded-full bg-emerald-500 animate-pulse"></span> Operational
                        </p>
                    </div>
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
            <div class="p-6 space-y-3 bg-white border shadow-xs border-amber-200/80 rounded-3xl">
                <div class="flex items-center gap-2 mb-1">
                    <i class="text-xs fa-solid fa-bell text-amber-500"></i>
                    <h4 class="text-xs font-extrabold tracking-wider uppercase text-slate-400">Attention Required</h4>
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
                                <p class="text-xs font-extrabold sm:text-sm text-amber-950">Transfer Request Outbound:
                                    {{ $t->sparepart->item_name }}</p>
                                <p class="text-[11px] sm:text-xs font-medium text-amber-800 mt-0.5">
                                    Target Destination: <strong>{{ $t->toSite->machine_name }}</strong> &bull; Qty:
                                    <strong>{{ $t->qty }} {{ $t->sparepart->uom }}</strong> &bull;
                                    Condition: <span class="font-bold uppercase">{{ $t->from_condition }}</span>
                                </p>
                            </div>
                        </div>
                        <form action="{{ route('movement.approve', $t->id) }}" method="POST">
                            @csrf
                            <button type="submit"
                                class="w-full px-5 py-2.5 text-xs font-bold text-white transition-all bg-amber-600 hover:bg-amber-700 rounded-xl shadow-md shadow-amber-600/20 active:scale-95 shrink-0">
                                Approve & Deduct Stock
                            </button>
                        </form>
                    </div>
                @endforeach

                @if (Auth::user()?->role === 'superadmin' ||
                        (Auth::user()?->role === 'admin_site' && Auth::user()?->site_id === $siteData->id))
                    @foreach ($pendingReceipts as $t)
                        <div
                            class="flex flex-col justify-between gap-4 p-4 border border-blue-200 bg-blue-50/60 rounded-2xl md:flex-row md:items-center">
                            <div class="flex items-center gap-3.5">
                                <div
                                    class="flex items-center justify-center w-10 h-10 font-bold text-blue-600 bg-blue-100/80 rounded-xl shrink-0">
                                    <i class="text-base fa-solid fa-box-archive"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-extrabold text-blue-950 sm:text-sm">Incoming Items In-Transit:
                                        {{ $t->sparepart->item_name }}</p>
                                    <p class="text-[11px] sm:text-xs font-medium text-blue-800 mt-0.5">
                                        Origin: <strong>{{ $t->fromSite->machine_name }}</strong> &bull; Incoming
                                        Condition:
                                        <span class="font-bold uppercase">{{ $t->condition }}</span>
                                    </p>
                                </div>
                            </div>
                            <form action="{{ route('movement.receive', $t->id) }}" method="POST">
                                @csrf
                                <button type="submit"
                                    class="w-full px-5 py-2.5 text-xs font-bold text-white transition-all bg-blue-600 hover:bg-blue-700 rounded-xl shadow-md shadow-blue-600/20 active:scale-95 shrink-0">
                                    Confirm Receipt
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
                                (Auth::user()?->role === 'admin_site' && Auth::user()?->site_id === $siteData->id))
                            <button onclick="openCreateModal()" type="button"
                                class="flex items-center gap-2 px-4 py-2.5 text-xs font-bold text-white transition-all bg-slate-900 hover:bg-blue-600 rounded-xl shadow-md active:scale-95">
                                <i class="text-xs fa-solid fa-plus"></i> Add Sparepart
                            </button>
                            <button onclick="openImportModal()" type="button"
                                class="flex items-center gap-2 px-4 py-2.5 text-xs font-bold text-blue-700 transition-all bg-blue-50 border border-blue-100 rounded-xl hover:bg-blue-600 hover:text-white active:scale-95">
                                <i class="fa-solid fa-file-import"></i> Import Excel
                            </button>
                            <a href="{{ route('sparepart.export', $slug) }}"
                                class="flex items-center gap-2 px-4 py-2.5 text-xs font-bold text-emerald-700 transition-all bg-emerald-50 border border-emerald-100 rounded-xl hover:bg-emerald-600 hover:text-white active:scale-95">
                                <i class="fa-solid fa-file-export"></i> Export Data
                            </a>
                        @endif
                    </div>

                    {{-- Filters & Search --}}
                    <div class="flex flex-col items-center w-full gap-3 sm:flex-row xl:w-auto">
                        <div class="relative w-full sm:w-48">
                            <select id="filter-condition"
                                class="w-full py-2.5 pl-3.5 pr-10 text-xs font-bold transition-all border outline-none appearance-none text-slate-700 bg-white border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 shadow-2xs">
                                <option value="">All Conditions</option>
                                <option value="new">NEW</option>
                                <option value="used-good">USED (Good)</option>
                                <option value="damaged">DAMAGED</option>
                                <option value="repair">REPAIR</option>
                            </select>
                            <i
                                class="fa-solid fa-chevron-down absolute right-3.5 top-3.5 text-slate-400 pointer-events-none text-[10px]"></i>
                        </div>

                        <div class="relative w-full sm:w-72">
                            <span
                                class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 pointer-events-none">
                                <i class="text-xs fa-solid fa-magnifying-glass"></i>
                            </span>
                            <input type="text" name="search" id="search" placeholder="Search Name or Serial..."
                                value="{{ request('search') }}"
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
                        <h3 class="text-base font-extrabold text-slate-900">Add New Sparepart</h3>
                        <p class="text-[10px] font-bold tracking-wider uppercase text-slate-400">Inbound Stock &bull;
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
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Item Name</label>
                        <input type="text" name="item_name" required placeholder="e.g. Roller Conveyor"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition-all font-medium text-slate-800">
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Category</label>
                        <select name="category_id"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition-all font-medium text-slate-800">
                            <option value="">-- Select Category --</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Serial
                            Number</label>
                        <input type="text" name="serial_number" placeholder="SN-XXXXX (Optional)"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition-all font-medium text-slate-800">
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Type / Model</label>
                        <input type="text" name="type" required placeholder="e.g. FS6000-X1"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition-all font-medium text-slate-800">
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Quantity &
                            UOM</label>
                        <div class="flex gap-0">
                            <input type="number" name="qty" required min="1" value="1"
                                class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-r-0 border-slate-200 rounded-l-xl bg-slate-50 focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition-all font-bold text-slate-800">
                            <select name="uom" required
                                class="w-28 px-2 py-2.5 text-xs font-bold border border-slate-200 bg-slate-100 rounded-r-xl outline-none focus:ring-4 focus:ring-emerald-500/10 text-slate-700">
                                <option value="" disabled>Unit</option>
                                <option value="PCS" selected>PCS</option>
                                <option value="SET">SET</option>
                                <option value="UNIT">UNIT</option>
                            </select>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Condition</label>
                        <select name="condition" required
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition-all font-medium text-slate-800">
                            <option value="" disabled selected>Select Condition</option>
                            <option value="new">NEW</option>
                            <option value="used-good">USED (Good)</option>
                            <option value="damaged">DAMAGED</option>
                            <option value="repair">REPAIRED</option>
                        </select>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Sparepart Image</label>
                    <div class="flex flex-col items-center justify-center w-full gap-3">
                        <label id="upload-label"
                            class="flex flex-col items-center justify-center w-full transition-colors border-2 border-dashed cursor-pointer h-28 rounded-2xl border-slate-200 bg-slate-50 hover:bg-slate-100">
                            <div class="flex flex-col items-center justify-center py-4">
                                <i class="mb-1 text-xl fa-solid fa-cloud-arrow-up text-slate-400"></i>
                                <p class="text-xs font-bold text-slate-600">Click to upload or drag & drop</p>
                                <p class="text-[10px] text-slate-400 uppercase mt-0.5">PNG, JPG, WEBP (Max 2MB)</p>
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
                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Notes / Remarks</label>
                    <textarea name="note" rows="2" placeholder="Add additional information..."
                        class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition-all text-slate-800 font-medium"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                    <button type="button" onclick="closeCreateModal()"
                        class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-800 transition-colors">Cancel</button>
                    <button type="submit"
                        class="px-6 py-2.5 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-md shadow-emerald-600/20 active:scale-[0.98] transition-all">
                        <i class="mr-1.5 fa-solid fa-floppy-disk"></i> Save Sparepart
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL IMPORT EXCEL --}}
    <div id="modal-import" class="{{ $modalBase }}">
        <div class="w-full max-w-md p-6 bg-white border shadow-2xl border-slate-100 rounded-3xl">
            <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
                <h3 class="text-base font-extrabold text-slate-900">Import Spareparts via Excel</h3>
                <button onclick="closeImportModal()" type="button" class="text-slate-400 hover:text-slate-600">
                    <i class="text-base fa-solid fa-xmark"></i>
                </button>
            </div>
            <form id="form-import" action="{{ route('sparepart.import', $slug) }}" method="POST"
                enctype="multipart/form-data">
                @csrf
                <div id="import-dropzone"
                    class="flex flex-col items-center justify-center w-full transition-all border-2 border-dashed cursor-pointer h-36 border-slate-200 rounded-2xl hover:border-blue-500 hover:bg-blue-50/50">
                    <i id="dropzone-icon" class="mb-2 text-3xl text-slate-400 fa-solid fa-cloud-arrow-up"></i>
                    <span id="dropzone-text" class="text-xs font-bold text-slate-700">Click or drag Excel file here</span>
                    <span id="dropzone-hint" class="mt-1 text-[10px] text-slate-400">.xlsx / .xls / .csv &bull; Max
                        10MB</span>
                    <input type="file" id="import-file-input" name="file" class="hidden"
                        accept=".xlsx,.xls,.csv">
                </div>
                <button type="submit" id="btn-submit-import"
                    class="hidden w-full px-5 py-3 mt-4 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-md shadow-blue-600/20 active:scale-[0.98] transition-all">
                    <i class="mr-1.5 fa-solid fa-file-import"></i> Submit & Import Data
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
                        <h3 class="text-base font-extrabold text-slate-900">Edit Sparepart</h3>
                        <p class="text-[10px] font-bold tracking-wider uppercase text-slate-400">Inventory Management
                            &bull; Update Record</p>
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
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Item Name</label>
                        <input type="text" id="edit_item_name" name="item_name" required
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all font-medium text-slate-800">
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Category</label>
                        <select id="edit_category_id" name="category_id"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all font-medium text-slate-800">
                            <option value="">-- Select Category --</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Serial
                            Number</label>
                        <input type="text" id="edit_serial_number" name="serial_number"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all font-medium text-slate-800">
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Type / Model</label>
                        <input type="text" id="edit_type" name="type" required
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all font-medium text-slate-800">
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">UOM</label>
                        <select id="edit_uom" name="uom" required
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all font-medium text-slate-800">
                            <option value="PCS">PCS</option>
                            <option value="SET">SET</option>
                            <option value="UNIT">UNIT</option>
                        </select>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Sparepart Image</label>
                    <div class="flex flex-col items-center justify-center w-full gap-3">
                        <label id="edit-upload-label"
                            class="flex flex-col items-center justify-center w-full transition-colors border-2 border-dashed cursor-pointer h-28 rounded-2xl border-slate-200 bg-slate-50 hover:bg-slate-100">
                            <div class="flex flex-col items-center justify-center py-4">
                                <i class="mb-1 text-xl fa-solid fa-cloud-arrow-up text-slate-400"></i>
                                <p class="text-xs font-bold text-slate-600">Click to upload or drag & drop</p>
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
                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Notes / Remarks</label>
                    <textarea id="edit_note" name="note" rows="2"
                        class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all font-medium text-slate-800"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                    <button type="button" onclick="closeEditModal()"
                        class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-800 transition-colors">Cancel</button>
                    <button type="submit"
                        class="px-6 py-2.5 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-md shadow-blue-600/20 active:scale-[0.98] transition-all">
                        Update Sparepart
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
                        <h3 class="text-base font-extrabold text-slate-900">Adjust Status / Stock</h3>
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
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Current Condition</p>
                        <span id="display-current-condition"
                            class="inline-block mt-1 px-2.5 py-0.5 rounded-lg text-xs font-black bg-slate-200 text-slate-700 uppercase"></span>
                    </div>
                    <div class="border-l border-slate-200">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Available Stock</p>
                        <p id="current-stock-display" class="mt-0.5 text-sm font-black text-slate-900"></p>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Units To Adjust</label>
                    <div class="relative">
                        <input type="number" name="qty_to_move" id="input-qty-adjust" required min="1"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-bold bg-white border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none text-slate-800">
                        <span id="adjust-uom-badge"
                            class="absolute right-3.5 top-3 text-xs font-bold text-slate-400 uppercase">Units</span>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">New Target
                        Condition</label>
                    <div class="relative">
                        <select name="new_condition" id="select-condition-adjust" required
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-bold bg-white border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none appearance-none text-slate-800">
                            <option value="new">NEW</option>
                            <option value="used-good">USED (Good)</option>
                            <option value="damaged">DAMAGED</option>
                            <option value="repair">REPAIRED</option>
                        </select>
                        <i
                            class="fa-solid fa-chevron-down absolute right-3.5 top-3.5 text-slate-400 pointer-events-none text-[10px]"></i>
                    </div>
                </div>

                <div id="adjustment-hint-box"
                    class="flex gap-2.5 p-3.5 text-xs leading-normal text-blue-800 border border-blue-100 bg-blue-50 rounded-2xl">
                    <i class="fa-solid fa-circle-info mt-0.5 shrink-0 text-blue-600"></i>
                    <span id="adjustment-hint-text">Enter unit quantity to preview live calculation.</span>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                    <button type="button" onclick="closeAdjustModal()"
                        class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-800 transition-colors">Cancel</button>
                    <button type="submit" id="btn-submit-adjust"
                        class="px-5 py-2.5 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-md shadow-blue-600/20 active:scale-[0.98] transition-all">Save
                        Changes</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function openEditModal(btn) {
            const item = JSON.parse(btn.getAttribute('data-item'));
            const modal = document.getElementById('modal-edit');
            const form = document.getElementById('form-edit');

            const sparepartId = item.sparepart_id ? item.sparepart_id : item.id;
            const itemName = item.sparepart ? item.sparepart.item_name : item.item_name;
            const categoryId = item.sparepart ? item.sparepart.category_id : item.category_id;
            const serialNumber = item.sparepart ? item.sparepart.serial_number : item.serial_number;
            const type = item.sparepart ? item.sparepart.type : item.type;
            const uom = item.sparepart ? item.sparepart.uom : item.uom;
            const note = item.sparepart ? item.sparepart.note : item.note;

            form.action = "/sparepart/{{ $slug }}/" + sparepartId;

            document.getElementById('edit_item_name').value = itemName || '';
            document.getElementById('edit_category_id').value = categoryId || '';
            document.getElementById('edit_serial_number').value = serialNumber || '';
            document.getElementById('edit_type').value = type || '';
            document.getElementById('edit_uom').value = uom || 'PCS';
            document.getElementById('edit_note').value = note || '';

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeEditModal() {
            const modal = document.getElementById('modal-edit');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

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

        function openCreateModal() {
            const m = document.getElementById('modal-create');
            m.classList.remove('hidden');
            m.classList.add('flex');
        }

        function closeCreateModal() {
            const m = document.getElementById('modal-create');
            m.classList.add('hidden');
            m.classList.remove('flex');
            resetImage();
        }

        function openImportModal() {
            const modal = document.getElementById('modal-import');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeImportModal() {
            const modal = document.getElementById('modal-import');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        let maxAvailableStock = 0;
        let currentActiveCondition = '';

        function openAdjustModal(id, name, qty, condition, uom = 'Units') {
            const modal = document.getElementById('modal-adjust');
            const form = document.getElementById('form-adjust');
            const slug = "{{ $slug }}";

            maxAvailableStock = parseInt(qty);
            currentActiveCondition = condition;

            form.action = "/inventory/" + slug + "/adjust/" + id;

            document.getElementById('adjust-item-name').innerText = name;
            document.getElementById('current-stock-display').innerText = qty + " " + uom;
            document.getElementById('adjust-uom-badge').innerText = uom;
            document.getElementById('display-current-condition').innerText = condition.replace('-', ' ');
            document.getElementById('input-current-condition').value = condition;

            const qtyInput = document.getElementById('input-qty-adjust');
            qtyInput.value = qty;
            qtyInput.max = qty;

            document.getElementById('select-condition-adjust').value = condition;

            calculateLiveHint();
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeAdjustModal() {
            const modal = document.getElementById('modal-adjust');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        const qtyInput = document.getElementById('input-qty-adjust');
        const conditionSelect = document.getElementById('select-condition-adjust');
        const hintBox = document.getElementById('adjustment-hint-box');
        const hintText = document.getElementById('adjustment-hint-text');
        const btnSubmit = document.getElementById('btn-submit-adjust');

        function calculateLiveHint() {
            if (!qtyInput) return;
            const enteredQty = parseInt(qtyInput.value) || 0;
            const selectedCondition = conditionSelect.value;

            if (enteredQty <= 0) {
                hintBox.className =
                    "flex gap-2.5 p-3.5 bg-rose-50 border border-rose-200 rounded-2xl text-xs text-rose-800";
                hintText.innerHTML = "Unit quantity adjustment must be at least 1 unit.";
                btnSubmit.disabled = true;
                return;
            }

            if (enteredQty > maxAvailableStock) {
                hintBox.className =
                    "flex gap-2.5 p-3.5 bg-rose-50 border border-rose-200 rounded-2xl text-xs text-rose-800";
                hintText.innerHTML = "Quantity exceeds available stock quantity (Maximum: " + maxAvailableStock +
                    " units).";
                btnSubmit.disabled = true;
                return;
            }

            btnSubmit.disabled = false;

            if (currentActiveCondition === selectedCondition) {
                hintBox.className =
                    "flex gap-2.5 p-3.5 bg-blue-50 border border-blue-200 rounded-2xl text-xs text-blue-800";
                hintText.innerHTML =
                    "<b>Total Stock Correction:</b> The overall stock for this condition will be set to <b>" + enteredQty +
                    " units</b>.";
            } else if (enteredQty === maxAvailableStock) {
                hintBox.className =
                    "flex gap-2.5 p-3.5 bg-emerald-50 border border-emerald-200 rounded-2xl text-xs text-emerald-800";
                hintText.innerHTML = "<b>Full Condition Shift:</b> All stock (" + enteredQty +
                    " units) will be transferred completely to <b>" + selectedCondition.toUpperCase() + "</b> status.";
            } else {
                hintBox.className =
                    "flex gap-2.5 p-3.5 bg-amber-50 border border-amber-200 rounded-2xl text-xs text-amber-800";
                hintText.innerHTML = "<b>Partial Condition Split:</b> <b>" + enteredQty +
                    " units</b> will be split to <b>" + selectedCondition.toUpperCase() + "</b> status, leaving <b>" + (
                        maxAvailableStock - enteredQty) + " units</b> in previous status.";
            }
        }

        if (qtyInput && conditionSelect) {
            qtyInput.addEventListener('input', calculateLiveHint);
            conditionSelect.addEventListener('change', calculateLiveHint);
        }

        // Live Search & Filter AJAX
        const searchInput = document.getElementById('search');
        const conditionFilter = document.getElementById('filter-condition');
        const tableContainer = document.getElementById('table-container');
        let delayTimer;

        function fetchFilteredSpareparts() {
            const query = searchInput ? searchInput.value : '';
            const condition = conditionFilter ? conditionFilter.value : '';

            tableContainer.classList.add('opacity-50');

            fetch("{{ route('sparepart.index', $siteData->slug) }}?search=" + encodeURIComponent(query) + "&condition=" +
                    encodeURIComponent(condition), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                .then(response => response.text())
                .then(html => {
                    tableContainer.innerHTML = html;
                    tableContainer.classList.remove('opacity-50');
                })
                .catch(error => {
                    console.error('Error Live Search:', error);
                    tableContainer.classList.remove('opacity-50');
                });
        }

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(delayTimer);
                delayTimer = setTimeout(fetchFilteredSpareparts, 300);
            });
        }

        if (conditionFilter) {
            conditionFilter.addEventListener('change', fetchFilteredSpareparts);
        }
    </script>
@endpush
