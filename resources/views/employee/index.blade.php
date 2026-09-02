@extends('layout.master')

@section('title', 'Employee Directory')

@section('content')
    <div class="w-full space-y-6">

        {{-- ALERT NOTIFIKASI SUCCESS / ERROR --}}
        @if (session('success'))
            <div
                class="flex items-center justify-between p-4 text-xs font-bold border border-emerald-200 rounded-2xl bg-emerald-50/80 text-emerald-800 sm:text-sm shadow-2xs">
                <div class="flex items-center gap-2.5">
                    <i class="text-base fa-solid fa-circle-check text-emerald-600"></i>
                    <span>{{ session('success') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()"
                    class="cursor-pointer text-emerald-600 hover:text-emerald-900">&times;</button>
            </div>
        @endif

        @if (session('error'))
            <div
                class="flex items-center justify-between p-4 text-xs font-bold border border-rose-200 rounded-2xl bg-rose-50/80 text-rose-800 sm:text-sm shadow-2xs">
                <div class="flex items-center gap-2.5">
                    <i class="text-base fa-solid fa-triangle-exclamation text-rose-600"></i>
                    <span>{{ session('error') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()"
                    class="cursor-pointer text-rose-600 hover:text-rose-900">&times;</button>
            </div>
        @endif

        @if ($errors->any())
            <div
                class="p-4 space-y-1 text-xs font-bold border border-rose-200 rounded-2xl bg-rose-50/80 text-rose-800 sm:text-sm shadow-2xs">
                <div class="flex items-center gap-2.5 mb-1 text-rose-900">
                    <i class="text-base fa-solid fa-circle-xmark text-rose-600"></i>
                    <span>Proses Impor Gagal:</span>
                </div>
                <ul class="font-medium list-disc list-inside ps-6 text-rose-700">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- 1. HEADER CARD --}}
        <div class="p-6 bg-white border shadow-xs sm:p-8 border-slate-200/80 rounded-3xl">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div
                        class="inline-flex items-center gap-2 px-3 py-1 mb-2 text-xs font-bold text-blue-700 border border-blue-100 rounded-full bg-blue-50">
                        <i class="fa-solid fa-users text-[10px]"></i> Human Resources
                    </div>
                    <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl text-slate-900">
                        Employee Management
                    </h1>
                    <p class="mt-1 text-xs font-semibold sm:text-sm text-slate-500">
                        Directory of site staff, positions, salary allocations, and employment tenures.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2 shrink-0">
                    {{-- TOMBOL IMPORT EXCEL --}}
                    <button type="button" onclick="openImportModal()"
                        class="flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-bold text-emerald-700 transition-all bg-emerald-50 border border-emerald-200/80 hover:bg-emerald-600 hover:text-white rounded-xl active:scale-95 cursor-pointer">
                        <i class="fa-solid fa-file-import"></i> Import Excel
                    </button>

                    {{-- TOMBOL EXPORT EXCEL --}}
                    <a id="btn-export-excel" href="{{ route('employee.export', request()->query()) }}" up-follow="false"
                        download
                        class="flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-bold text-slate-700 transition-all bg-slate-100 border border-slate-200/80 hover:bg-slate-800 hover:text-white rounded-xl active:scale-95">
                        <i class="fa-solid fa-file-excel"></i> Export Excel
                    </a>

                    {{-- TOMBOL ADD EMPLOYEE --}}
                    <a href="{{ route('employee.create') }}"
                        class="flex items-center justify-center gap-2 px-5 py-2.5 text-xs font-bold text-white transition-all bg-blue-600 shadow-md hover:bg-blue-700 rounded-xl shadow-blue-600/20 active:scale-95">
                        <i class="text-xs fa-solid fa-user-plus"></i> Add Employee
                    </a>
                </div>
            </div>
        </div>

        {{-- 2. MAIN TABLE & FILTER CONTAINER CARD --}}
        <div class="overflow-hidden bg-white border shadow-xs border-slate-200/80 rounded-3xl">
            <div class="p-5 border-b sm:p-6 border-slate-100 bg-slate-50/30">
                <div class="flex flex-col gap-4">
                    {{-- Search Bar --}}
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="relative w-full md:w-96">
                            <div
                                class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                                <i class="text-xs fa-solid fa-magnifying-glass"></i>
                            </div>
                            <input type="text" name="search" id="search"
                                placeholder="Search name, NIK, email, position..." value="{{ request('search') }}"
                                class="filter-trigger block w-full py-2.5 pl-10 pr-3.5 text-xs sm:text-sm font-medium border border-slate-200 rounded-xl bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none text-slate-800 placeholder-slate-400 shadow-2xs">
                        </div>

                        <button type="button" id="btn-reset-filter"
                            class="flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-bold text-slate-600 transition-colors bg-white border border-slate-200 rounded-xl hover:bg-slate-100 hover:text-slate-800 active:scale-95 shrink-0 shadow-2xs cursor-pointer">
                            <i class="fa-solid fa-rotate-left text-[11px]"></i> Reset Filter
                        </button>
                    </div>

                    {{-- Filters --}}
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-3">
                        <div>
                            <label for="filter_status"
                                class="block mb-1.5 text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">Status</label>
                            <select id="filter_status"
                                class="filter-trigger block w-full py-2.5 px-3.5 text-xs sm:text-sm font-bold border border-slate-200 rounded-xl bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none text-slate-800 cursor-pointer">
                                <option value="">All Statuses</option>
                                <option value="Permanent" {{ request('status') == 'Permanent' ? 'selected' : '' }}>Permanent
                                </option>
                                <option value="Contract" {{ request('status') == 'Contract' ? 'selected' : '' }}>Contract
                                </option>
                                <option value="Probation" {{ request('status') == 'Probation' ? 'selected' : '' }}>Probation
                                </option>
                                <option value="Daily" {{ request('status') == 'Daily' ? 'selected' : '' }}>Daily</option>
                            </select>
                        </div>

                        <div>
                            <label for="filter_site"
                                class="block mb-1.5 text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">Site
                                Location</label>
                            <select id="filter_site"
                                class="filter-trigger block w-full py-2.5 px-3.5 text-xs sm:text-sm font-bold border border-slate-200 rounded-xl bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none text-slate-800 cursor-pointer">
                                <option value="">All Sites</option>
                                @if (isset($sites))
                                    @foreach ($sites as $site)
                                        <option value="{{ $site->id }}"
                                            {{ request('site_id') == $site->id ? 'selected' : '' }}>
                                            {{ $site->machine_name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        <div>
                            <label for="filter_branch"
                                class="block mb-1.5 text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">Branch</label>
                            <select id="filter_branch"
                                class="filter-trigger block w-full py-2.5 px-3.5 text-xs sm:text-sm font-bold border border-slate-200 rounded-xl bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none text-slate-800 cursor-pointer">
                                <option value="">All Branches</option>
                                @if (isset($branches))
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}"
                                            {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->branch_name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div id="table-container" class="transition-opacity duration-200">
                @include('employee.table', ['employees' => $employees])
            </div>
        </div>
    </div>

    {{-- MODAL IMPORT EXCEL --}}
    <div id="importModal" onclick="if(event.target===this) closeImportModal()"
        class="fixed inset-0 z-50 items-center justify-center hidden p-4 transition-all duration-200 bg-slate-900/60 backdrop-blur-xs">
        <div class="w-full max-w-md mx-auto overflow-hidden bg-white border shadow-2xl border-slate-100 rounded-3xl">
            <div class="flex items-center justify-between px-6 py-5 text-white bg-slate-900">
                <h5 class="flex items-center gap-2.5 text-xs font-extrabold tracking-wider uppercase">
                    <i class="text-emerald-400 fa-solid fa-file-excel"></i> Import Employees Excel
                </h5>
                <button type="button" onclick="closeImportModal()"
                    class="flex items-center justify-center w-8 h-8 transition-colors rounded-lg cursor-pointer text-slate-400 hover:text-white hover:bg-slate-800">&times;</button>
            </div>
            <form action="{{ route('employee.import') }}" method="POST" enctype="multipart/form-data"
                up-target="#main-content" up-on-accepted="closeImportModal()" class="p-6 space-y-4">
                @csrf
                <div>
                    <label class="block mb-1.5 text-xs font-bold uppercase tracking-wider text-slate-700">Select Excel File
                        (.xlsx, .xls)</label>
                    <input type="file" name="file" required accept=".xlsx, .xls, .csv"
                        class="block w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer border border-slate-200 rounded-xl">
                </div>

                <div class="flex justify-end gap-2 pt-4 border-t border-slate-100">
                    <button type="button" onclick="closeImportModal()"
                        class="px-4 py-2 text-xs font-bold cursor-pointer text-slate-600 hover:text-slate-800">Cancel</button>
                    <button type="submit"
                        class="px-5 py-2 text-xs font-bold text-white shadow-md cursor-pointer bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-emerald-600/20 active:scale-95">Upload
                        & Import</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL POP-UP DETAIL KARYAWAN --}}
    <div id="employeeDetailModal" onclick="if(event.target===this) closeEmployeeModal()"
        class="fixed inset-0 z-50 items-center justify-center hidden p-4 transition-all duration-200 bg-slate-900/60 backdrop-blur-xs">
        <div class="w-full max-w-2xl mx-auto overflow-hidden bg-white border shadow-2xl border-slate-100 rounded-3xl">
            <div class="flex items-center justify-between px-6 py-5 text-white bg-slate-900">
                <h5 class="flex items-center gap-2.5 text-xs font-extrabold tracking-wider uppercase">
                    <i class="text-blue-400 fa-solid fa-id-card"></i> Employee Specifications
                </h5>
                <button type="button" onclick="closeEmployeeModal()"
                    class="flex items-center justify-center w-8 h-8 transition-colors rounded-lg cursor-pointer text-slate-400 hover:text-white hover:bg-slate-800">&times;</button>
            </div>

            <div class="p-6 space-y-5 max-h-[80vh] overflow-y-auto">
                <div class="flex items-center gap-4 pb-4 border-b border-slate-100">
                    <div
                        class="flex items-center justify-center w-12 h-12 text-lg font-black text-blue-700 border rounded-2xl border-blue-200/80 bg-blue-50 shrink-0">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold leading-snug sm:text-lg text-slate-900" id="detail_name">-
                        </h3>
                        <p class="text-xs font-bold text-slate-500 mt-0.5" id="detail_position">-</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 text-xs md:grid-cols-3">
                    <div class="p-3.5 border border-slate-200/80 rounded-2xl bg-slate-50/50">
                        <span class="block mb-1 font-bold text-slate-400 uppercase text-[10px] tracking-wider">NIK</span>
                        <strong class="font-mono text-xs sm:text-sm text-slate-800" id="detail_nik">-</strong>
                    </div>
                    <div class="p-3.5 border border-slate-200/80 rounded-2xl bg-slate-50/50">
                        <span class="block mb-1 font-bold text-slate-400 uppercase text-[10px] tracking-wider">Email</span>
                        <strong class="block text-xs truncate sm:text-sm text-slate-800" id="detail_email">-</strong>
                    </div>
                    <div class="p-3.5 border border-slate-200/80 rounded-2xl bg-slate-50/50">
                        <span class="block mb-1 font-bold text-slate-400 uppercase text-[10px] tracking-wider">Phone</span>
                        <strong class="text-xs sm:text-sm text-slate-800" id="detail_phone">-</strong>
                    </div>
                    <div class="p-3.5 border border-slate-200/80 rounded-2xl bg-slate-50/50">
                        <span class="block mb-1 font-bold text-slate-400 uppercase text-[10px] tracking-wider">Site
                            Location</span>
                        <strong class="text-xs sm:text-sm text-slate-800" id="detail_site">-</strong>
                    </div>
                    <div class="p-3.5 border border-slate-200/80 rounded-2xl bg-slate-50/50">
                        <span
                            class="block mb-1 font-bold text-slate-400 uppercase text-[10px] tracking-wider">Branch</span>
                        <strong class="text-xs sm:text-sm text-slate-800" id="detail_branch">-</strong>
                    </div>
                    <div class="p-3.5 border border-slate-200/80 rounded-2xl bg-slate-50/50">
                        <span
                            class="block mb-1 font-bold text-slate-400 uppercase text-[10px] tracking-wider">Status</span>
                        <span id="detail_status_badge">-</span>
                    </div>
                    <div class="p-3.5 border border-emerald-200/80 rounded-2xl bg-emerald-50/40 col-span-2 md:col-span-1">
                        <span class="block mb-1 font-bold text-emerald-600 uppercase text-[10px] tracking-wider">Gaji
                            Pokok</span>
                        <strong class="text-sm font-black text-emerald-700" id="detail_basic_salary">Rp 0</strong>
                    </div>
                    <div class="p-3.5 border border-slate-200/80 rounded-2xl bg-slate-50/50">
                        <span class="block mb-1 font-bold text-slate-400 uppercase text-[10px] tracking-wider">Bank
                            Account</span>
                        <strong class="block text-xs sm:text-sm text-slate-800" id="detail_bank">-</strong>
                    </div>
                    <div class="p-3.5 border border-slate-200/80 rounded-2xl bg-slate-50/50">
                        <span class="block mb-1 font-bold text-slate-400 uppercase text-[10px] tracking-wider">MCU</span>
                        <span id="detail_mcu_badge">-</span>
                    </div>
                    <div class="p-3.5 border border-slate-200/80 rounded-2xl bg-slate-50/50">
                        <span class="block mb-1 font-bold text-slate-400 uppercase text-[10px] tracking-wider">TLD
                            Badge</span>
                        <span id="detail_tld_badge">-</span>
                    </div>
                    <div class="p-3.5 border border-slate-200/80 rounded-2xl bg-slate-50/50">
                        <span
                            class="block mb-1 font-bold text-slate-400 uppercase text-[10px] tracking-wider">Tenure</span>
                        <strong class="text-xs font-bold text-blue-600 sm:text-sm" id="detail_tenure">-</strong>
                    </div>
                    <div class="p-3.5 border border-slate-200/80 rounded-2xl bg-slate-50/50">
                        <span class="block mb-1 font-bold text-slate-400 uppercase text-[10px] tracking-wider">Join
                            Date</span>
                        <strong class="text-xs sm:text-sm text-slate-800" id="detail_join_date">-</strong>
                    </div>
                    <div class="p-3.5 border border-slate-200/80 rounded-2xl bg-slate-50/50">
                        <span class="block mb-1 font-bold text-slate-400 uppercase text-[10px] tracking-wider">Contract
                            Start Date</span>
                        <strong class="text-xs sm:text-sm text-slate-800" id="detail_contract_start_date">-</strong>
                    </div>
                </div>

                <div class="pt-2">
                    <h6 class="mb-2 text-xs font-extrabold tracking-wider uppercase text-slate-700">Histori Perubahan Gaji
                    </h6>
                    <div class="overflow-hidden border border-slate-200 rounded-2xl">
                        <table class="w-full text-xs text-left">
                            <thead class="text-[10px] font-extrabold uppercase tracking-wider text-slate-500 bg-slate-100">
                                <tr>
                                    <th class="p-3">Tanggal</th>
                                    <th class="p-3">Lama</th>
                                    <th class="p-3">Baru</th>
                                    <th class="p-3">Alasan</th>
                                </tr>
                            </thead>
                            <tbody id="detail_salary_history_body"
                                class="font-medium divide-y divide-slate-100 text-slate-700">
                                <tr>
                                    <td colspan="4" class="p-4 text-center text-slate-400">Belum ada riwayat perubahan.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="flex justify-end px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                <button type="button" onclick="closeEmployeeModal()"
                    class="px-5 py-2.5 text-xs font-bold transition-all bg-white border text-slate-700 border-slate-200 rounded-xl hover:bg-slate-100 active:scale-95 shadow-2xs cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        var delayTimer = delayTimer || null;

        function fetchFilteredData(targetUrl = null) {
            clearTimeout(delayTimer);
            delayTimer = setTimeout(() => {
                const searchInput = document.getElementById('search');
                const statusInput = document.getElementById('filter_status');
                const siteInput = document.getElementById('filter_site');
                const branchInput = document.getElementById('filter_branch');
                const tableContainer = document.getElementById('table-container');

                if (!tableContainer) return;

                tableContainer.style.opacity = '0.5';

                const search = searchInput ? searchInput.value.trim() : '';
                const status = statusInput ? statusInput.value : '';
                const site = siteInput ? siteInput.value : '';
                const branch = branchInput ? branchInput.value : '';

                const params = new URLSearchParams({
                    search,
                    status,
                    site_id: site,
                    branch_id: branch
                });

                // Sync tombol Export Excel dengan parameter pencarian saat ini
                const exportBtn = document.getElementById('btn-export-excel');
                if (exportBtn) {
                    exportBtn.href = `{{ route('employee.export') }}?${params.toString()}`;
                }

                let fetchUrl = targetUrl ? targetUrl : `{{ route('employee.index') }}?${params.toString()}`;

                fetch(fetchUrl, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(res => res.text())
                    .then(html => {
                        tableContainer.innerHTML = html;
                        tableContainer.style.opacity = '1';
                        bindPaginationLinks();
                    })
                    .catch(err => {
                        console.error('Fetch Error:', err);
                        tableContainer.style.opacity = '1';
                    });
            }, 300);
        }

        function bindPaginationLinks() {
            const paginationLinks = document.querySelectorAll(
                '#table-container .pagination a, #table-container a[rel="prev"], #table-container a[rel="next"]');
            paginationLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    fetchFilteredData(this.href);
                });
            });
        }

        function initEmployeePageScripts() {
            const filterTriggers = document.querySelectorAll('.filter-trigger');
            const btnResetFilter = document.getElementById('btn-reset-filter');

            filterTriggers.forEach(el => {
                el.removeEventListener(el.tagName === 'INPUT' ? 'input' : 'change', handleFilterChange);
                el.addEventListener(el.tagName === 'INPUT' ? 'input' : 'change', handleFilterChange);
            });

            if (btnResetFilter) {
                btnResetFilter.onclick = function() {
                    if (document.getElementById('search')) document.getElementById('search').value = '';
                    if (document.getElementById('filter_status')) document.getElementById('filter_status').value = '';
                    if (document.getElementById('filter_site')) document.getElementById('filter_site').value = '';
                    if (document.getElementById('filter_branch')) document.getElementById('filter_branch').value = '';
                    fetchFilteredData();
                };
            }

            bindPaginationLinks();
        }

        function handleFilterChange() {
            fetchFilteredData();
        }

        function openImportModal() {
            let modal = document.getElementById('importModal');
            if (!modal) return;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        }

        function closeImportModal() {
            let modal = document.getElementById('importModal');
            if (!modal) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        }

        function showEmployeeDetail(id) {
            fetch(`/employee/${id}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    document.getElementById('detail_name').innerText = data.name || '-';
                    document.getElementById('detail_nik').innerText = data.nik || '-';
                    document.getElementById('detail_email').innerText = data.email || '-';
                    document.getElementById('detail_phone').innerText = data.phone_number || '-';
                    document.getElementById('detail_position').innerText = data.position || 'Staff';
                    document.getElementById('detail_site').innerText = data.site ? data.site.machine_name : '-';
                    document.getElementById('detail_branch').innerText = (data.site && data.site.branch) ? data.site
                        .branch.branch_name : '-';
                    document.getElementById('detail_tenure').innerText = data.tenure_formatted || '-';
                    document.getElementById('detail_join_date').innerText = data.join_date_formatted || '-';
                    document.getElementById('detail_contract_start_date').innerText = data.contract_start_formatted ||
                        '-';
                    document.getElementById('detail_basic_salary').innerText = data.basic_salary_formatted || 'Rp 0';
                    document.getElementById('detail_bank').innerText = (data.bank_name && data.bank_account_number) ?
                        `${data.bank_name} - ${data.bank_account_number}` : '-';

                    let statusBadge =
                        `<span class="px-2.5 py-1 text-[10px] font-extrabold text-slate-700 bg-slate-100 border border-slate-200 rounded-full uppercase">${data.status || 'Active'}</span>`;
                    document.getElementById('detail_status_badge').innerHTML = statusBadge;

                    let mcuBadge = (data.mcu === 'yes') ?
                        `<span class="px-2 py-0.5 text-[10px] font-black text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-md">YES</span>` :
                        `<span class="px-2 py-0.5 text-[10px] font-black text-slate-500 bg-slate-100 border border-slate-200 rounded-md">NO</span>`;
                    document.getElementById('detail_mcu_badge').innerHTML = mcuBadge;

                    let tldBadge = (data.tld === 'yes') ?
                        `<span class="px-2 py-0.5 text-[10px] font-black text-blue-700 bg-blue-50 border border-blue-200 rounded-md">YES</span>` :
                        `<span class="px-2 py-0.5 text-[10px] font-black text-slate-500 bg-slate-100 border border-slate-200 rounded-md">NO</span>`;
                    document.getElementById('detail_tld_badge').innerHTML = tldBadge;

                    let historyBody = document.getElementById('detail_salary_history_body');
                    if (data.salary_histories && data.salary_histories.length > 0) {
                        let rows = '';
                        data.salary_histories.forEach(h => {
                            let dt = new Date(h.created_at).toLocaleDateString('id-ID');
                            let oldSal = 'Rp ' + new Intl.NumberFormat('id-ID').format(h.old_salary || 0);
                            let newSal = 'Rp ' + new Intl.NumberFormat('id-ID').format(h.new_salary || 0);
                            rows += `<tr>
                                <td class="p-3">${dt}</td>
                                <td class="p-3 font-semibold text-slate-400">${oldSal}</td>
                                <td class="p-3 font-bold text-emerald-600">${newSal}</td>
                                <td class="p-3">${h.reason || '-'}</td>
                            </tr>`;
                        });
                        historyBody.innerHTML = rows;
                    } else {
                        historyBody.innerHTML =
                            '<tr><td colspan="4" class="p-4 text-center text-slate-400">Belum ada riwayat perubahan gaji.</td></tr>';
                    }

                    let modal = document.getElementById('employeeDetailModal');
                    if (modal) {
                        modal.classList.remove('hidden');
                        modal.classList.add('flex');
                        document.body.classList.add('overflow-hidden');
                    }
                })
                .catch(err => {
                    console.error('Failed to load employee details:', err);
                });
        }

        function closeEmployeeModal() {
            let modal = document.getElementById('employeeDetailModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.classList.remove('overflow-hidden');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            initEmployeePageScripts();

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeImportModal();
                    closeEmployeeModal();
                }
            });
        });

        if (window.up) {
            up.compiler('#main-content', function() {
                initEmployeePageScripts();
            });
        }
    </script>
@endpush
