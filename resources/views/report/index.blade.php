@extends('layout.master')

@section('title', 'Failure Reports & Breakdown Logs')

@section('content')
    <div class="w-full space-y-6">

        {{-- 1. HEADER CARD --}}
        <div class="p-6 bg-white border shadow-xs sm:p-8 border-slate-200/80 rounded-3xl">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <div
                        class="inline-flex items-center gap-2 px-3 py-1 mb-2 text-xs font-bold border rounded-full bg-rose-50 border-rose-100 text-rose-700">
                        <i class="fa-solid fa-triangle-exclamation text-[10px]"></i> Maintenance Log
                    </div>
                    <h1 class="text-2xl font-extrabold tracking-tight uppercase sm:text-3xl text-slate-900">
                        Failure Reports
                    </h1>
                    <p class="mt-1 text-xs font-semibold sm:text-sm text-slate-500">
                        Registry of technical failures, field breakdown logs, and machinery repair records.
                    </p>
                </div>

                {{-- Total Reports Counter --}}
                <div
                    class="flex items-center gap-6 pt-4 border-t md:pt-0 md:border-t-0 md:border-l border-slate-200 md:pl-8 shrink-0">
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Total Reports</p>
                        <p class="text-2xl font-black text-slate-900 mt-0.5 tracking-tight">{{ $report->total() }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. FAILURE QUEUE CARD (PENDING DAMAGED STOCK) --}}
        <div class="p-6 space-y-4 bg-white border shadow-xs border-amber-200/80 rounded-3xl">
            <div class="flex items-center gap-2">
                <span class="relative flex w-2.5 h-2.5">
                    <span
                        class="absolute inline-flex w-full h-full rounded-full opacity-75 animate-ping bg-amber-400"></span>
                    <span class="relative inline-flex w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                </span>
                <h3 class="text-xs font-extrabold tracking-wider uppercase text-amber-800">
                    Pending Report Queue (Damaged Inventory Stock)
                </h3>
            </div>

            <div class="overflow-hidden border border-slate-200/80 rounded-2xl">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[650px]">
                        <thead>
                            <tr
                                class="border-b bg-amber-50/50 border-slate-200/80 text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">
                                <th class="px-6 py-3.5">Sparepart Info</th>
                                <th class="px-6 py-3.5">Site Location</th>
                                <th class="px-6 py-3.5 text-center">Damaged Qty</th>
                                <th class="px-6 py-3.5 text-center w-40">Action</th>
                            </tr>
                        </thead>
                        <tbody class="text-xs font-medium divide-y divide-slate-100 text-slate-700">
                            @forelse($failureQueue as $item)
                                <tr class="transition-colors hover:bg-amber-50/20">
                                    <td class="px-6 py-3.5">
                                        <span
                                            class="block text-sm font-bold leading-snug text-slate-900">{{ $item->sparepart?->item_name ?? 'N/A' }}</span>
                                        <span class="text-[10px] font-mono text-slate-400">SN:
                                            {{ $item->sparepart?->serial_number ?? '-' }}</span>
                                    </td>
                                    <td class="px-6 py-3.5 font-bold text-slate-700">
                                        {{ $item->site?->machine_name ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-3.5 font-black text-center text-rose-600 text-sm">
                                        {{ $item->qty }} <span
                                            class="text-[10px] font-bold text-slate-400 uppercase">{{ $item->sparepart?->uom ?? 'PCS' }}</span>
                                    </td>
                                    <td class="px-6 py-3.5 text-center">
                                        @if (in_array(Auth::user()?->role, ['superadmin', 'team_leader']))
                                            <a href="{{ route('report.create', ['stock_id' => $item->id]) }}"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[10px] font-bold text-white bg-amber-600 hover:bg-amber-700 rounded-xl shadow-xs transition-all active:scale-95">
                                                <i class="fa-solid fa-file-pen"></i> Process Log
                                            </a>
                                        @else
                                            <span class="text-xs italic text-slate-400">Waiting Admin</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="p-6 text-xs italic font-medium text-center text-slate-400">
                                        <i class="mr-1.5 fa-solid fa-circle-check text-emerald-500"></i> No newly damaged
                                        spareparts awaiting breakdown logs.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- 3. MAIN TABLE & TOOLBAR CARD --}}
        <div class="overflow-hidden bg-white border shadow-xs border-slate-200/80 rounded-3xl">

            {{-- TOOLBAR SECTION --}}
            <div class="p-5 border-b sm:p-6 border-slate-100 bg-slate-50/30">
                <div class="flex flex-col justify-between gap-4 xl:flex-row xl:items-center">

                    {{-- Action Buttons --}}
                    <div class="flex flex-wrap items-center gap-2.5">
                        @if (in_array(Auth::user()?->role, ['superadmin', 'team_leader']))
                            <a href="{{ route('report.create') }}"
                                class="flex items-center gap-2 px-4 py-2.5 text-xs font-bold text-white transition-all bg-rose-600 hover:bg-rose-700 rounded-xl shadow-md shadow-rose-600/20 active:scale-95">
                                <i class="text-xs fa-solid fa-plus"></i> Add Manual Entry
                            </a>

                            <button id="btn-delete" type="button" onclick="confirmDeleteSelected()"
                                class="flex items-center gap-2 px-4 py-2.5 text-xs font-bold transition-all bg-white border text-slate-600 border-slate-200 rounded-xl hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 active:scale-95 cursor-pointer">
                                <i class="fa-solid fa-trash-can"></i> Delete Selected
                            </button>

                            <div class="hidden w-px h-6 mx-1 bg-slate-200 sm:block"></div>

                            <a href="{{ route('report.export') }}"
                                class="flex items-center gap-2 px-4 py-2.5 text-xs font-bold text-emerald-700 transition-all bg-emerald-50 border border-emerald-100 rounded-xl hover:bg-emerald-600 hover:text-white active:scale-95">
                                <i class="fa-solid fa-file-excel"></i> Export Excel
                            </a>
                        @endif
                    </div>

                    {{-- Search Bar --}}
                    <div class="relative w-full xl:w-80">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 pointer-events-none">
                            <i class="text-xs fa-solid fa-magnifying-glass"></i>
                        </span>
                        <input type="text" id="search" name="search" data-route="{{ route('report.search') }}"
                            placeholder="Search report or site..." autocomplete="off" value="{{ request('search') }}"
                            class="w-full py-2.5 pl-10 pr-10 text-xs sm:text-sm font-medium border border-slate-200 rounded-xl bg-white focus:border-rose-500 focus:ring-4 focus:ring-rose-500/10 transition-all outline-none shadow-2xs text-slate-800">

                        <div id="search-loader" class="absolute hidden right-3.5 top-3 text-rose-600 text-xs">
                            <i class="fa-solid fa-circle-notch fa-spin"></i>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TABLE SECTION --}}
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[700px]">
                    <thead>
                        <tr
                            class="text-[10px] font-extrabold uppercase tracking-wider text-slate-500 bg-slate-50 border-b border-slate-100">
                            @if (Auth::user()?->role === 'superadmin')
                                <th class="w-12 px-6 py-4 text-center">
                                    <input type="checkbox" id="select_all_id"
                                        class="w-4 h-4 rounded cursor-pointer text-rose-600 border-slate-300 focus:ring-rose-500">
                                </th>
                            @endif
                            <th class="w-16 px-6 py-4 text-center">No</th>
                            <th class="px-6 py-4">Site Machine</th>
                            <th class="px-6 py-4">Attendant / Reporter</th>
                            <th class="px-6 py-4 text-center">Failure Date</th>
                            @if (Auth::user()?->role === 'superadmin')
                                <th class="w-32 px-6 py-4 text-center">Actions</th>
                            @endif
                        </tr>
                    </thead>

                    <tbody id="table-body" class="text-xs font-medium divide-y divide-slate-100 text-slate-700">
                        @include('report.table', [
                            'data' => $report,
                            'routePrefix' => 'report',
                        ])
                    </tbody>
                </table>
            </div>

            {{-- Pagination Info --}}
            <div class="p-4 border-t sm:p-6 border-slate-100 bg-slate-50/50">
                {{ $report->links() }}
            </div>

        </div>
    </div>

    {{-- DELETE CONFIRMATION MODAL --}}
    <div id="modal-delete-report"
        class="fixed inset-0 z-50 flex items-center justify-center hidden p-4 transition-all bg-slate-900/60 backdrop-blur-xs">
        <div class="relative w-full max-w-sm p-6 text-center bg-white border shadow-2xl border-slate-100 rounded-3xl">
            <div class="flex justify-center mb-4">
                <div
                    class="flex items-center justify-center w-12 h-12 border rounded-2xl bg-rose-50 border-rose-100 text-rose-600">
                    <i class="text-xl fa-solid fa-triangle-exclamation"></i>
                </div>
            </div>
            <h3 class="mb-1 text-base font-extrabold text-slate-900">Confirm Deletion</h3>
            <p class="mb-6 text-xs font-medium text-slate-500">
                Are you sure you want to delete <strong id="delete-report-name" class="text-slate-900">this record</strong>?
                <span class="block mt-1 font-semibold text-rose-600">This action cannot be undone.</span>
            </p>
            <form id="form-delete-report" method="POST">
                @csrf
                @method('DELETE')
                <div class="flex items-center justify-end gap-3">
                    <button type="button" onclick="closeDeleteReportModal()"
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
@endsection

@push('scripts')
    <script>
        // Checkbox Select All Logic
        const selectAll = document.getElementById('select_all_id');
        if (selectAll) {
            selectAll.addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.sub_chk');
                checkboxes.forEach(cb => cb.checked = this.checked);
            });
        }

        function openDeleteReportModal(url, name) {
            const m = document.getElementById('modal-delete-report');
            document.getElementById('form-delete-report').action = url;
            document.getElementById('delete-report-name').innerText = name;
            m.classList.remove('hidden');
            m.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        }

        function closeDeleteReportModal() {
            const m = document.getElementById('modal-delete-report');
            m.classList.add('hidden');
            m.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        }

        // Live Search AJAX with Debounce
        const searchInput = document.getElementById('search');
        const searchLoader = document.getElementById('search-loader');
        const tableBody = document.getElementById('table-body');
        let searchTimer;

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimer);
                if (searchLoader) searchLoader.classList.remove('hidden');

                searchTimer = setTimeout(() => {
                    const query = this.value.trim();
                    const route = this.getAttribute('data-route');

                    fetch(`${route}?search=${encodeURIComponent(query)}`, {
                            headers: {
                                "X-Requested-With": "XMLHttpRequest"
                            }
                        })
                        .then(res => res.text())
                        .then(html => {
                            tableBody.innerHTML = html;
                        })
                        .catch(err => console.error(err))
                        .finally(() => {
                            if (searchLoader) searchLoader.classList.add('hidden');
                        });
                }, 350);
            });
        }
    </script>
@endpush
