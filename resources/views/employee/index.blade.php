@extends('layout.master')

@section('title', 'Employee Directory')

@section('content')
    <div class="w-full space-y-6">

        {{-- HEADER SECTION --}}
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl text-slate-900">
                    Employee Management
                </h1>
                @if (Auth::user()->role === 'admin_site')
                    <p class="mt-1 text-xs font-semibold text-blue-600 flex items-center gap-1.5">
                        <i class="fa-solid fa-building-user"></i> Site Admin: {{ Auth::user()->site->machine_name ?? '-' }}
                    </p>
                @else
                    <p class="mt-0.5 text-xs sm:text-sm font-medium text-slate-500">Directory of site staff, positions, and
                        employment tenures.</p>
                @endif
            </div>

            <div class="flex items-center gap-3 shrink-0">
                <a href="{{ route('employee.create') }}"
                    class="flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-bold text-white transition-all bg-blue-600 rounded-xl hover:bg-blue-700 shadow-md shadow-blue-600/20 active:scale-95">
                    <i class="fa-solid fa-user-plus"></i> Add Employee
                </a>
            </div>
        </div>

        {{-- MAIN CARD CONTAINER --}}
        <div class="overflow-hidden bg-white border shadow-sm border-slate-200/80 rounded-2xl sm:rounded-3xl">
            <div class="p-5 border-b sm:p-6 border-slate-100 bg-slate-50/50">
                <div class="flex flex-col gap-4">
                    {{-- Row Search Bar & Clear Filter --}}
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="relative w-full md:w-96">
                            <div
                                class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                                <i class="text-xs fa-solid fa-magnifying-glass"></i>
                            </div>
                            <input type="text" name="search" id="search"
                                placeholder="Search employee name or position..." value="{{ request('search') }}"
                                class="filter-trigger block w-full py-2.5 pl-10 pr-3.5 text-xs sm:text-sm font-medium border border-slate-200 rounded-xl bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none text-slate-700 placeholder-slate-400">
                        </div>

                        <button type="button" id="btn-reset-filter"
                            class="flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-bold text-slate-500 transition-colors bg-white border border-slate-200 rounded-xl hover:bg-slate-100 hover:text-slate-700 shrink-0">
                            <i class="fa-solid fa-rotate-left"></i> Reset Filter
                        </button>
                    </div>

                    {{-- Row Filter Options --}}
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-3">
                        {{-- Status Filter --}}
                        <div>
                            <label for="filter_status"
                                class="block mb-1 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Status</label>
                            <select id="filter_status"
                                class="filter-trigger block w-full py-2.5 px-3 text-xs sm:text-sm font-medium border border-slate-200 rounded-xl bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none text-slate-700">
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

                        {{-- Site Filter --}}
                        <div>
                            <label for="filter_site"
                                class="block mb-1 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Site
                                Location</label>
                            <select id="filter_site"
                                class="filter-trigger block w-full py-2.5 px-3 text-xs sm:text-sm font-medium border border-slate-200 rounded-xl bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none text-slate-700">
                                <option value="">All Sites</option>
                                @if (isset($sites))
                                    @foreach ($sites as $site)
                                        <option value="{{ $site->id }}"
                                            {{ request('site_id') == $site->id ? 'selected' : '' }}>
                                            {{ $site->machine_name }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        {{-- Branch Filter --}}
                        <div>
                            <label for="filter_branch"
                                class="block mb-1 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Branch</label>
                            <select id="filter_branch"
                                class="filter-trigger block w-full py-2.5 px-3 text-xs sm:text-sm font-medium border border-slate-200 rounded-xl bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none text-slate-700">
                                <option value="">All Branches</option>
                                @if (isset($branches))
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}"
                                            {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->branch_name }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div id="table-container">
                @include('employee.table', ['employees' => $employees])
            </div>
        </div>
    </div>

    {{-- MODAL POP-UP DETAIL KARYAWAN --}}
    <div id="employeeDetailModal"
        class="fixed inset-0 z-50 items-center justify-center hidden p-4 transition-all duration-200 bg-slate-900/60 backdrop-blur-xs">
        <div class="w-full max-w-lg mx-auto overflow-hidden bg-white border shadow-2xl border-slate-200 rounded-2xl">
            <div class="flex items-center justify-between px-6 py-4 text-white bg-slate-900">
                <h5 class="flex items-center gap-2 text-sm font-extrabold tracking-wide uppercase">
                    <i class="text-blue-400 fa-solid fa-id-card"></i> Employee Details
                </h5>
                <button type="button" onclick="closeEmployeeModal()"
                    class="text-xl leading-none transition-colors text-slate-400 hover:text-white">&times;</button>
            </div>

            <div class="p-6 space-y-4">
                <div class="flex items-center gap-4 pb-4 border-b border-slate-100">
                    <div
                        class="flex items-center justify-center w-12 h-12 text-lg font-bold text-blue-600 border rounded-full border-blue-200/80 bg-blue-50 shrink-0">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold sm:text-lg text-slate-900" id="detail_name">-</h3>
                        <p class="text-xs font-semibold text-slate-500" id="detail_position">-</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 text-xs">
                    <div class="p-3 border border-slate-100 rounded-xl bg-slate-50/50">
                        <span class="block mb-1 font-bold text-slate-400 uppercase text-[10px] tracking-wider">Site
                            Location</span>
                        <strong class="text-xs sm:text-sm text-slate-800" id="detail_site">-</strong>
                    </div>
                    <div class="p-3 border border-slate-100 rounded-xl bg-slate-50/50">
                        <span class="block mb-1 font-bold text-slate-400 uppercase text-[10px] tracking-wider">Branch</span>
                        <strong class="text-xs sm:text-sm text-slate-800" id="detail_branch">-</strong>
                    </div>
                    <div class="p-3 border border-slate-100 rounded-xl bg-slate-50/50">
                        <span class="block mb-1 font-bold text-slate-400 uppercase text-[10px] tracking-wider">Employment
                            Status</span>
                        <span id="detail_status_badge">-</span>
                    </div>
                    <div class="p-3 border border-slate-100 rounded-xl bg-slate-50/50">
                        <span class="block mb-1 font-bold text-slate-400 uppercase text-[10px] tracking-wider">Tenure</span>
                        <strong class="text-xs text-blue-600 sm:text-sm" id="detail_tenure">-</strong>
                    </div>
                    <div class="p-3 border border-slate-100 rounded-xl bg-slate-50/50">
                        <span class="block mb-1 font-bold text-slate-400 uppercase text-[10px] tracking-wider">Join
                            Date</span>
                        <strong class="text-xs sm:text-sm text-slate-800" id="detail_join_date">-</strong>
                    </div>
                    <div class="p-3 border border-slate-100 rounded-xl bg-slate-50/50">
                        <span class="block mb-1 font-bold text-slate-400 uppercase text-[10px] tracking-wider">Contract
                            Start</span>
                        <strong class="text-xs sm:text-sm text-slate-800" id="detail_contract_start">-</strong>
                    </div>
                </div>
            </div>

            <div class="flex justify-end px-6 py-3.5 border-t border-slate-100 bg-slate-50/50">
                <button type="button" onclick="closeEmployeeModal()"
                    class="px-4 py-2 text-xs font-bold transition-all bg-white border text-slate-600 border-slate-200 rounded-xl hover:bg-slate-100">
                    Close
                </button>
            </div>
        </div>
    </div>

    <script>
        const filterTriggers = document.querySelectorAll('.filter-trigger');
        const tableContainer = document.getElementById('table-container');
        const btnResetFilter = document.getElementById('btn-reset-filter');
        let delayTimer;

        // Fetch data berdasarkan seluruh input filter
        function fetchFilteredData() {
            clearTimeout(delayTimer);

            delayTimer = setTimeout(() => {
                const search = document.getElementById('search').value;
                const status = document.getElementById('filter_status').value;
                const site = document.getElementById('filter_site').value;
                const branch = document.getElementById('filter_branch').value;

                const params = new URLSearchParams({
                    search: search,
                    status: status,
                    site_id: site,
                    branch_id: branch
                });

                fetch(`{{ route('employee.index') }}?${params.toString()}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.text())
                    .then(html => {
                        tableContainer.innerHTML = html;
                    })
                    .catch(error => console.error('Error fetching filtered results:', error));
            }, 300);
        }

        // Jalankan fetchFilteredData ketika filter berubah
        filterTriggers.forEach(element => {
            if (element.tagName === 'INPUT') {
                element.addEventListener('input', fetchFilteredData);
            } else {
                element.addEventListener('change', fetchFilteredData);
            }
        });

        // Event Listener Tombol Reset Filter
        if (btnResetFilter) {
            btnResetFilter.addEventListener('click', function() {
                document.getElementById('search').value = '';
                document.getElementById('filter_status').value = '';
                document.getElementById('filter_site').value = '';
                document.getElementById('filter_branch').value = '';
                fetchFilteredData();
            });
        }

        // Paginasi via AJAX
        document.addEventListener('click', function(e) {
            const paginationLink = e.target.closest('#table-container nav a');
            if (paginationLink) {
                e.preventDefault();
                fetch(paginationLink.href, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.text())
                    .then(html => {
                        tableContainer.innerHTML = html;
                    })
                    .catch(error => console.error('Error fetching pagination:', error));
            }
        });

        // Detail Modal Function
        function showEmployeeDetail(id) {
            fetch(`/employee/${id}`, {
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                .then(async res => {
                    if (!res.ok) {
                        let errMsg = 'Failed to fetch employee details (' + res.status + ')';
                        try {
                            let errJson = await res.json();
                            if (errJson.message) errMsg = errJson.message;
                        } catch (e) {}
                        throw new Error(errMsg);
                    }
                    return res.json();
                })
                .then(data => {
                    document.getElementById('detail_name').innerText = data.name || '-';
                    document.getElementById('detail_position').innerText = data.position || 'Staff';
                    document.getElementById('detail_site').innerText = data.site ? data.site.machine_name : '-';
                    document.getElementById('detail_branch').innerText = (data.site && data.site.branch) ? data.site
                        .branch.branch_name : '-';
                    document.getElementById('detail_tenure').innerText = data.tenure_formatted || '-';
                    document.getElementById('detail_join_date').innerText = data.join_date_formatted || '-';
                    document.getElementById('detail_contract_start').innerText = data.contract_start_formatted || '-';

                    let statusBadge =
                        '<span class="px-2.5 py-1 text-xs font-bold text-slate-600 bg-slate-100 rounded-full">' + (data
                            .status || 'Active') + '</span>';
                    if (data.status === 'Permanent') {
                        statusBadge =
                            '<span class="px-2.5 py-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200/60 rounded-full uppercase">Permanent</span>';
                    } else if (data.status === 'Contract') {
                        statusBadge =
                            '<span class="px-2.5 py-1 text-[11px] font-bold text-blue-700 bg-blue-50 border border-blue-200/60 rounded-full uppercase">Contract</span>';
                    } else if (data.status === 'Probation') {
                        statusBadge =
                            '<span class="px-2.5 py-1 text-[11px] font-bold text-amber-700 bg-amber-50 border border-amber-200/60 rounded-full uppercase">Probation</span>';
                    } else if (data.status === 'Daily') {
                        statusBadge =
                            '<span class="px-2.5 py-1 text-[11px] font-bold text-purple-700 bg-purple-50 border border-purple-200/60 rounded-full uppercase">Daily</span>';
                    }
                    document.getElementById('detail_status_badge').innerHTML = statusBadge;

                    let modal = document.getElementById('employeeDetailModal');
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                })
                .catch(err => alert(err.message));
        }

        function closeEmployeeModal() {
            let modal = document.getElementById('employeeDetailModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    </script>
@endsection
