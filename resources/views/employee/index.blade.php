@extends('layout.master')

@section('title', 'Employee Directory')

@section('content')
    <div class="w-full space-y-6">

        {{-- 1. HEADER CARD (TERPISAH) --}}
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
                    @if (Auth::user()?->role === 'admin_site')
                        <p class="mt-1 text-xs font-semibold text-blue-600 flex items-center gap-1.5">
                            <i class="fa-solid fa-building-user"></i> Site Admin:
                            {{ Auth::user()->site->machine_name ?? '-' }}
                        </p>
                    @else
                        <p class="mt-1 text-xs font-semibold sm:text-sm text-slate-500">
                            Directory of site staff, positions, salary allocations, and employment tenures.
                        </p>
                    @endif
                </div>

                <div class="flex items-center gap-3 shrink-0">
                    <a href="{{ route('employee.create') }}"
                        class="flex items-center justify-center gap-2 px-5 py-3 text-xs font-bold text-white transition-all bg-blue-600 shadow-md sm:text-sm hover:bg-blue-700 rounded-xl shadow-blue-600/20 active:scale-95">
                        <i class="text-xs fa-solid fa-user-plus"></i> Add Employee
                    </a>
                </div>
            </div>
        </div>

        {{-- 2. MAIN TABLE & FILTER CONTAINER CARD --}}
        <div class="overflow-hidden bg-white border shadow-xs border-slate-200/80 rounded-3xl">
            <div class="p-5 border-b sm:p-6 border-slate-100 bg-slate-50/30">
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
                                class="filter-trigger block w-full py-2.5 pl-10 pr-3.5 text-xs sm:text-sm font-medium border border-slate-200 rounded-xl bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none text-slate-800 placeholder-slate-400 shadow-2xs">
                        </div>

                        <button type="button" id="btn-reset-filter"
                            class="flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-bold text-slate-600 transition-colors bg-white border border-slate-200 rounded-xl hover:bg-slate-100 hover:text-slate-800 active:scale-95 shrink-0 shadow-2xs">
                            <i class="fa-solid fa-rotate-left text-[11px]"></i> Reset Filter
                        </button>
                    </div>

                    {{-- Row Filter Options --}}
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-3">
                        <div>
                            <label for="filter_status"
                                class="block mb-1.5 text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">Status</label>
                            <select id="filter_status"
                                class="filter-trigger block w-full py-2.5 px-3.5 text-xs sm:text-sm font-bold border border-slate-200 rounded-xl bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none text-slate-800">
                                <option value="">All Statuses</option>
                                <option value="Permanent">Permanent</option>
                                <option value="Contract">Contract</option>
                                <option value="Probation">Probation</option>
                                <option value="Daily">Daily</option>
                            </select>
                        </div>

                        <div>
                            <label for="filter_site"
                                class="block mb-1.5 text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">Site
                                Location</label>
                            <select id="filter_site"
                                class="filter-trigger block w-full py-2.5 px-3.5 text-xs sm:text-sm font-bold border border-slate-200 rounded-xl bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none text-slate-800">
                                <option value="">All Sites</option>
                                @if (isset($sites))
                                    @foreach ($sites as $site)
                                        <option value="{{ $site->id }}">{{ $site->machine_name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        <div>
                            <label for="filter_branch"
                                class="block mb-1.5 text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">Branch</label>
                            <select id="filter_branch"
                                class="filter-trigger block w-full py-2.5 px-3.5 text-xs sm:text-sm font-bold border border-slate-200 rounded-xl bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none text-slate-800">
                                <option value="">All Branches</option>
                                @if (isset($branches))
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}">{{ $branch->branch_name }}</option>
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
        <div class="w-full max-w-xl mx-auto overflow-hidden bg-white border shadow-2xl border-slate-100 rounded-3xl">
            <div class="flex items-center justify-between px-6 py-5 text-white bg-slate-900">
                <h5 class="flex items-center gap-2.5 text-xs font-extrabold tracking-wider uppercase">
                    <i class="text-blue-400 fa-solid fa-id-card"></i> Employee Specifications
                </h5>
                <button type="button" onclick="closeEmployeeModal()"
                    class="flex items-center justify-center w-8 h-8 transition-colors rounded-lg text-slate-400 hover:text-white hover:bg-slate-800">&times;</button>
            </div>

            <div class="p-6 space-y-5 max-h-[80vh] overflow-y-auto">
                <div class="flex items-center gap-4 pb-4 border-b border-slate-100">
                    <div
                        class="flex items-center justify-center w-12 h-12 text-lg font-black text-blue-700 border rounded-2xl border-blue-200/80 bg-blue-50 shrink-0">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold leading-snug sm:text-lg text-slate-900" id="detail_name">-</h3>
                        <p class="text-xs font-bold text-slate-500 mt-0.5" id="detail_position">-</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 text-xs">
                    <div class="p-3.5 border border-slate-200/80 rounded-2xl bg-slate-50/50">
                        <span class="block mb-1 font-bold text-slate-400 uppercase text-[10px] tracking-wider">Site
                            Location</span>
                        <strong class="text-xs sm:text-sm text-slate-800" id="detail_site">-</strong>
                    </div>
                    <div class="p-3.5 border border-slate-200/80 rounded-2xl bg-slate-50/50">
                        <span class="block mb-1 font-bold text-slate-400 uppercase text-[10px] tracking-wider">Branch</span>
                        <strong class="text-xs sm:text-sm text-slate-800" id="detail_branch">-</strong>
                    </div>
                    <div class="p-3.5 border border-emerald-200/80 rounded-2xl bg-emerald-50/40">
                        <span class="block mb-1 font-bold text-emerald-600 uppercase text-[10px] tracking-wider">Gaji Pokok
                            Saat Ini</span>
                        <strong class="text-sm font-black text-emerald-700" id="detail_basic_salary">Rp 0</strong>
                    </div>
                    <div class="p-3.5 border border-slate-200/80 rounded-2xl bg-slate-50/50">
                        <span class="block mb-1 font-bold text-slate-400 uppercase text-[10px] tracking-wider">Employment
                            Status</span>
                        <span id="detail_status_badge">-</span>
                    </div>
                    <div class="p-3.5 border border-slate-200/80 rounded-2xl bg-slate-50/50">
                        <span class="block mb-1 font-bold text-slate-400 uppercase text-[10px] tracking-wider">Tenure</span>
                        <strong class="text-xs font-bold text-blue-600 sm:text-sm" id="detail_tenure">-</strong>
                    </div>
                    <div class="p-3.5 border border-slate-200/80 rounded-2xl bg-slate-50/50">
                        <span class="block mb-1 font-bold text-slate-400 uppercase text-[10px] tracking-wider">Join
                            Date</span>
                        <strong class="text-xs sm:text-sm text-slate-800" id="detail_join_date">-</strong>
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
                    class="px-5 py-2.5 text-xs font-bold transition-all bg-white border text-slate-700 border-slate-200 rounded-xl hover:bg-slate-100 active:scale-95 shadow-2xs">
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

        function fetchFilteredData() {
            clearTimeout(delayTimer);
            delayTimer = setTimeout(() => {
                const search = document.getElementById('search').value;
                const status = document.getElementById('filter_status').value;
                const site = document.getElementById('filter_site').value;
                const branch = document.getElementById('filter_branch').value;

                const params = new URLSearchParams({
                    search,
                    status,
                    site_id: site,
                    branch_id: branch
                });

                fetch(`{{ route('employee.index') }}?${params.toString()}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(res => res.text())
                    .then(html => tableContainer.innerHTML = html)
                    .catch(err => console.error(err));
            }, 300);
        }

        filterTriggers.forEach(el => {
            el.addEventListener(el.tagName === 'INPUT' ? 'input' : 'change', fetchFilteredData);
        });

        if (btnResetFilter) {
            btnResetFilter.addEventListener('click', function() {
                document.getElementById('search').value = '';
                document.getElementById('filter_status').value = '';
                document.getElementById('filter_site').value = '';
                document.getElementById('filter_branch').value = '';
                fetchFilteredData();
            });
        }

        function showEmployeeDetail(id) {
            fetch(`/employee/${id}`, {
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    document.getElementById('detail_name').innerText = data.name || '-';
                    document.getElementById('detail_position').innerText = data.position || 'Staff';
                    document.getElementById('detail_site').innerText = data.site ? data.site.machine_name : '-';
                    document.getElementById('detail_branch').innerText = (data.site && data.site.branch) ? data.site
                        .branch.branch_name : '-';
                    document.getElementById('detail_tenure').innerText = data.tenure_formatted || '-';
                    document.getElementById('detail_join_date').innerText = data.join_date_formatted || '-';
                    document.getElementById('detail_basic_salary').innerText = data.basic_salary_formatted || 'Rp 0';

                    let statusBadge =
                        `<span class="px-2.5 py-1 text-[10px] font-extrabold text-slate-700 bg-slate-100 border border-slate-200 rounded-full uppercase">${data.status}</span>`;
                    document.getElementById('detail_status_badge').innerHTML = statusBadge;

                    // Render Histori Gaji
                    let historyBody = document.getElementById('detail_salary_history_body');
                    if (data.salary_histories && data.salary_histories.length > 0) {
                        let rows = '';
                        data.salary_histories.forEach(h => {
                            let dt = new Date(h.created_at).toLocaleDateString('id-ID');
                            let oldSal = 'Rp ' + new Intl.NumberFormat('id-ID').format(h.old_salary);
                            let newSal = 'Rp ' + new Intl.NumberFormat('id-ID').format(h.new_salary);
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
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    document.body.classList.add('overflow-hidden');
                });
        }

        function closeEmployeeModal() {
            let modal = document.getElementById('employeeDetailModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        }
    </script>
@endsection
