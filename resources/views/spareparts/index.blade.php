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

        {{-- 2. NOTIFICATION PANEL (PENDING MOVEMENT APPROVALS) --}}
        @php
            $pendingApprovals = $pendingApprovals ?? [];
            $pendingReceipts = $pendingReceipts ?? [];
        @endphp

        @if (count($pendingApprovals) > 0 || count($pendingReceipts) > 0)
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
                                    {{ $t->sparepart?->item_name }}</p>
                                <p class="text-[11px] sm:text-xs font-medium text-amber-800 mt-0.5">
                                    Target Destination: <strong>{{ $t->toSite?->machine_name }}</strong> &bull; Qty:
                                    <strong>{{ $t->qty }} {{ $t->sparepart?->uom }}</strong> &bull;
                                    Condition: <span class="font-bold uppercase">{{ $t->from_condition }}</span>
                                </p>
                            </div>
                        </div>
                        <form action="{{ route('movement.approve', $t->id) }}" method="POST">
                            @csrf
                            <button type="submit"
                                class="w-full px-5 py-2.5 text-xs font-bold text-white transition-all bg-amber-600 hover:bg-amber-700 rounded-xl shadow-md shadow-amber-600/20 active:scale-95 shrink-0 cursor-pointer">
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
                                        {{ $t->sparepart?->item_name }}</p>
                                    <p class="text-[11px] sm:text-xs font-medium text-blue-800 mt-0.5">
                                        Origin: <strong>{{ $t->fromSite?->machine_name }}</strong> &bull; Incoming
                                        Condition:
                                        <span class="font-bold uppercase">{{ $t->condition }}</span>
                                    </p>
                                </div>
                            </div>
                            <form action="{{ route('movement.receive', $t->id) }}" method="POST">
                                @csrf
                                <button type="submit"
                                    class="w-full px-5 py-2.5 text-xs font-bold text-white transition-all bg-blue-600 hover:bg-blue-700 rounded-xl shadow-md shadow-blue-600/20 active:scale-95 shrink-0 cursor-pointer">
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
                                class="flex items-center gap-2 px-4 py-2.5 text-xs font-bold text-white transition-all bg-slate-900 hover:bg-blue-600 rounded-xl shadow-md active:scale-95 cursor-pointer">
                                <i class="text-xs fa-solid fa-plus"></i> Add Sparepart
                            </button>
                            <button onclick="openImportModal()" type="button"
                                class="flex items-center gap-2 px-4 py-2.5 text-xs font-bold text-blue-700 transition-all bg-blue-50 border border-blue-100 rounded-xl hover:bg-blue-600 hover:text-white active:scale-95 cursor-pointer">
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
                                class="w-full py-2.5 pl-3.5 pr-10 text-xs font-bold transition-all border outline-none appearance-none text-slate-700 bg-white border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 shadow-2xs cursor-pointer">
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
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Item Name <span
                                class="text-rose-500">*</span></label>
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
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Type / Model <span
                                class="text-rose-500">*</span></label>
                        <input type="text" name="type" required placeholder="e.g. FS6000-X1"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition-all font-medium text-slate-800">
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Quantity &
                            UOM <span class="text-rose-500">*</span></label>
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
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Condition <span
                                class="text-rose-500">*</span></label>
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
                            <span class="text-[9px] font-bold uppercase tracking-widest opacity-60">No Image</span>
                        </div>
                    </div>
                    <div>
                        <h3 id="d_item_name" class="text-lg font-extrabold leading-snug text-slate-900"></h3>
                        <p id="d_type" class="font-mono text-xs font-semibold text-slate-500 mt-0.5"></p>
                        <div class="flex flex-wrap gap-1.5 mt-2">
                            <span id="d_serial_number"
                                class="px-2.5 py-0.5 bg-slate-100 border border-slate-200 text-slate-700 text-[10px] rounded-lg font-bold font-mono"></span>
                            <span id="d_source_data"
                                class="px-2.5 py-0.5 bg-emerald-50 border border-emerald-200 text-emerald-800 text-[10px] rounded-lg font-bold"></span>
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
                    <p class="flex items-center gap-2 mb-3 font-bold text-slate-800 uppercase tracking-wider text-[11px]">
                        <i class="text-blue-600 fa-solid fa-layer-group"></i> Stock Distribution Across Sites
                    </p>
                    <div class="overflow-hidden border border-slate-200/80 rounded-2xl">
                        <table class="w-full text-xs text-left">
                            <thead
                                class="font-bold text-slate-500 bg-slate-50 border-b border-slate-100 text-[10px] uppercase">
                                <tr>
                                    <th class="p-3">Site Location</th>
                                    <th class="p-3 text-center">Qty</th>
                                    <th class="p-3 text-center">Condition</th>
                                </tr>
                            </thead>
                            <tbody id="d_stock_table" class="font-medium divide-y divide-slate-100 text-slate-700">
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="p-6 bg-slate-50/50">
                    <p class="flex items-center gap-2 mb-4 font-bold text-slate-800 uppercase tracking-wider text-[11px]">
                        <i class="text-amber-600 fa-solid fa-clock-rotate-left"></i> Tracking Movement History
                    </p>
                    <div class="relative pl-5 border-l-2 border-amber-300 space-y-4 max-h-[250px] overflow-y-auto pr-2"
                        id="d_history"></div>
                </div>
            </div>

            <div class="p-4 text-right bg-white border-t border-slate-100 shrink-0">
                <button onclick="closeDetailModal()" type="button"
                    class="px-5 py-2.5 text-xs font-bold transition-colors text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl">
                    Close Details
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
            <h3 class="mb-1 text-base font-extrabold text-slate-900">Are you sure?</h3>
            <p class="mb-6 text-xs font-medium text-slate-500">You are about to delete <strong id="delete-item-name"
                    class="text-slate-900"></strong>.</p>
            <form id="form-confirm-delete" method="POST">
                @csrf @method('DELETE')
                <div class="flex items-center justify-end gap-3">
                    <button type="button" onclick="closeDeleteModal()"
                        class="w-full py-2.5 text-xs font-bold text-slate-600 hover:text-slate-800 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                        class="w-full py-2.5 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 active:scale-[0.98] transition-all rounded-xl shadow-md shadow-rose-600/20">
                        Yes, Delete
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
                    <h3 class="text-base font-extrabold text-slate-900">Transfer Request</h3>
                    <p id="move-asset-tag" class="font-mono text-[11px] font-bold text-amber-700 uppercase"></p>
                </div>
            </div>
            <form id="form-move" method="POST" class="p-6 space-y-4 overflow-y-auto">
                @csrf
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Destination Site</label>
                    <select name="to_site_id" required
                        class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-bold border border-slate-200 bg-slate-50 rounded-xl focus:bg-white focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 outline-none transition-all text-slate-800">
                        <option value="" disabled selected>Select Destination</option>
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
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Condition</label>
                        <select name="condition" id="target-condition" required
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-bold border border-slate-200 bg-slate-50 rounded-xl outline-none text-slate-800">
                            <option value="new">NEW</option>
                            <option value="used-good">USED GOOD</option>
                            <option value="damaged">DAMAGED</option>
                            <option value="repair">REPAIRED</option>
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Quantity</label>
                        <input type="number" name="qty" id="move-quantity" min="1" required
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs sm:text-sm font-bold text-slate-800">
                    </div>
                </div>
                <p id="max-info" class="text-[11px] font-bold text-right text-slate-400 italic"></p>
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                    <button type="button" onclick="closeMoveModal()"
                        class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-800 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-5 py-2.5 text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 rounded-xl shadow-md shadow-amber-600/20 active:scale-[0.98] transition-all">
                        Request Transfer
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- IMAGE VIEWER MODAL --}}
    <div id="image-viewer"
        class="fixed inset-0 z-[100] hidden bg-slate-950/90 backdrop-blur-md items-center justify-center p-4">
        <button onclick="closeImageViewer()" type="button"
            class="absolute z-10 p-2 text-white transition-transform hover:text-slate-300 top-4 right-4 hover:scale-110">
            <i class="text-3xl fa-solid fa-xmark"></i>
        </button>
        <img id="full-image" src=""
            class="max-w-full max-h-full transition-all duration-300 transform scale-95 shadow-2xl rounded-2xl"
            alt="Full Preview">
    </div>
@endsection

@push('scripts')
    <script>
        // Modal Handlers
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
            document.body.classList.add('overflow-hidden');
        }

        function closeEditModal() {
            const modal = document.getElementById('modal-edit');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
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
            if (input) input.value = "";
            if (previewImage) previewImage.src = "#";
            if (previewContainer) previewContainer.classList.add('hidden');
            if (uploadLabel) uploadLabel.classList.remove('hidden');
        }

        function openCreateModal() {
            const m = document.getElementById('modal-create');
            m.classList.remove('hidden');
            m.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        }

        function closeCreateModal() {
            const m = document.getElementById('modal-create');
            m.classList.add('hidden');
            m.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
            resetImage();
        }

        function openImportModal() {
            const modal = document.getElementById('modal-import');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        }

        function closeImportModal() {
            const modal = document.getElementById('modal-import');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
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
            document.body.classList.add('overflow-hidden');
        }

        function closeAdjustModal() {
            const modal = document.getElementById('modal-adjust');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
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

        function openDetailModal(item, sites) {
            document.getElementById('d_item_name').innerText = item.item_name;
            document.getElementById('d_type').innerText = "Type: " + (item.type || '-');
            document.getElementById('d_serial_number').innerText = "SN: " + (item.serial_number || '-');
            document.getElementById('d_source_data').innerText = "Source: " + (item.source_data || 'Manual Input');

            const imgElement = document.getElementById('d_image');
            const imgContainer = document.getElementById('d_image_container');
            const placeholder = document.getElementById('no-image-placeholder');

            if (item.image) {
                imgElement.src = `/storage/${item.image}`;
                imgContainer.classList.remove('hidden');
                imgElement.classList.remove('hidden');
                placeholder.classList.add('hidden');
                placeholder.classList.remove('flex');
            } else {
                imgContainer.classList.add('hidden');
                placeholder.classList.remove('hidden');
                placeholder.classList.add('flex');
            }

            const stockTable = document.getElementById('d_stock_table');
            stockTable.innerHTML = '';
            if (item.stocks && item.stocks.length > 0) {
                item.stocks.forEach(s => {
                    stockTable.innerHTML += `
                    <tr class="transition-colors hover:bg-slate-50">
                        <td class="p-3 font-bold text-slate-800">${s.site?.machine_name || 'N/A'}</td>
                        <td class="p-3 font-extrabold text-center text-blue-600">${s.qty}</td>
                        <td class="p-3 text-center"><span class="text-[9px] px-2 py-0.5 rounded-md bg-slate-100 font-bold uppercase text-slate-700">${s.condition}</span></td>
                    </tr>`;
                });
            } else {
                stockTable.innerHTML =
                    '<tr><td colspan="3" class="p-4 italic text-center text-slate-400">No active stock</td></tr>';
            }

            const historyContainer = document.getElementById('d_history');
            historyContainer.innerHTML = (item.histories && item.histories.length > 0) ? '' :
                '<p class="text-xs italic text-slate-400">No movement history recorded.</p>';
            if (item.histories) {
                item.histories.forEach(h => {
                    const date = new Date(h.created_at).toLocaleString('en-US', {
                        day: 'numeric',
                        month: 'short',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    historyContainer.innerHTML += `
                    <div class="relative">
                        <div class="absolute -left-[26px] mt-1.5 w-3.5 h-3.5 rounded-full bg-amber-500 border-2 border-white"></div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase">${date}</p>
                        <p class="text-xs font-bold text-slate-900 mt-0.5">${h.action}</p>
                        <p class="text-[11px] font-medium text-slate-600">${h.from_site?.machine_name || 'Initial'} &rarr; ${h.to_site?.machine_name || 'Unknown'}</p>
                        <p class="text-[10px] italic font-semibold text-slate-400 mt-0.5">Qty: ${h.qty} | Condition: ${h.condition}</p>
                    </div>`;
                });
            }

            const modal = document.getElementById('detailModal');
            const wrapper = document.getElementById('modalWrapper');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
            setTimeout(() => {
                wrapper.classList.replace('scale-95', 'scale-100');
                wrapper.classList.replace('opacity-0', 'opacity-100');
            }, 10);
        }

        function closeDetailModal() {
            const modal = document.getElementById('detailModal');
            const wrapper = document.getElementById('modalWrapper');
            wrapper.classList.replace('scale-100', 'scale-95');
            wrapper.classList.replace('opacity-100', 'opacity-0');
            document.body.classList.remove('overflow-hidden');
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }, 200);
        }

        function expandImage() {
            const imgElement = document.getElementById('d_image');
            if (imgElement.classList.contains('hidden')) return;

            const viewer = document.getElementById('image-viewer');
            const fullImg = document.getElementById('full-image');

            fullImg.src = imgElement.src;
            viewer.classList.remove('hidden');
            viewer.classList.add('flex');

            setTimeout(() => {
                fullImg.classList.replace('scale-95', 'scale-100');
            }, 10);
        }

        function closeImageViewer() {
            const viewer = document.getElementById('image-viewer');
            const fullImg = document.getElementById('full-image');
            fullImg.classList.replace('scale-100', 'scale-95');
            setTimeout(() => {
                viewer.classList.add('hidden');
                viewer.classList.remove('flex');
            }, 200);
        }

        function openDeleteModal(url, itemName) {
            document.getElementById('form-confirm-delete').action = url;
            document.getElementById('delete-item-name').innerText = itemName;
            const modal = document.getElementById('modal-delete');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
            setTimeout(() => {
                modal.querySelector('.modal-content').classList.replace('scale-95', 'scale-100');
                modal.querySelector('.modal-content').classList.replace('opacity-0', 'opacity-100');
            }, 10);
        }

        function closeDeleteModal() {
            const modal = document.getElementById('modal-delete');
            modal.querySelector('.modal-content').classList.replace('scale-100', 'scale-95');
            modal.querySelector('.modal-content').classList.replace('opacity-100', 'opacity-0');
            document.body.classList.remove('overflow-hidden');
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }, 200);
        }

        function openMoveModal(stockId, itemName, currentQty, currentCondition) {
            const modal = document.getElementById('modal-move');
            document.getElementById('move-asset-tag').innerText = itemName + " (" + currentCondition.toUpperCase() + ")";
            document.getElementById('form-move').action = "/movement/request/" + stockId;
            document.getElementById('target-condition').value = currentCondition;

            const qtyInput = document.getElementById('move-quantity');
            qtyInput.max = currentQty;
            qtyInput.value = 1;
            document.getElementById('max-info').innerText = "* Available stock: " + currentQty + " pcs";

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        }

        function closeMoveModal() {
            const modal = document.getElementById('modal-move');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        }

        window.onclick = function(event) {
            if (event.target.id === 'detailModal') closeDetailModal();
            if (event.target.id === 'modal-delete') closeDeleteModal();
            if (event.target.id === 'modal-move') closeMoveModal();
            if (event.target.id === 'image-viewer') closeImageViewer();
        }

        // Live Search & Filter AJAX
        const searchInput = document.getElementById('search');
        const conditionFilter = document.getElementById('filter-condition');
        const tableContainer = document.getElementById('table-container');
        let delayTimer;

        function fetchFilteredSpareparts(targetUrl = null) {
            const query = searchInput ? searchInput.value.trim() : '';
            const condition = conditionFilter ? conditionFilter.value : '';

            tableContainer.classList.add('opacity-50');

            let url = targetUrl ? new URL(targetUrl, window.location.origin) : new URL(
                "{{ route('sparepart.index', $siteData->slug) }}", window.location.origin);

            if (query) url.searchParams.set('search', query);
            else url.searchParams.delete('search');

            if (condition) url.searchParams.set('condition', condition);
            else url.searchParams.delete('condition');

            fetch(url.href, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.text())
                .then(html => {
                    tableContainer.innerHTML = html;
                    tableContainer.classList.remove('opacity-50');
                    window.history.pushState({}, '', url.href);
                })
                .catch(error => {
                    console.error('Error Live Search:', error);
                    tableContainer.classList.remove('opacity-50');
                });
        }

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(delayTimer);
                delayTimer = setTimeout(fetchFilteredSpareparts, 350);
            });
        }

        if (conditionFilter) {
            conditionFilter.addEventListener('change', function() {
                fetchFilteredSpareparts();
            });
        }
    </script>
@endpush
