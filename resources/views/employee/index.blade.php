@extends('layout.master')

@section('title', 'Daftar Karyawan')

@section('content')
    <div class="w-full px-6 py-8">

        @if (session('success'))
            <div
                class="flex items-center gap-2 p-4 mb-6 text-sm text-green-800 border border-green-200 bg-green-50 rounded-xl">
                <i class="text-base fa-solid fa-circle-check"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <div class="flex flex-col gap-2 mb-6">
            <h1 class="text-3xl font-extrabold tracking-tighter text-black">
                Employee Management
            </h1>
            @if (Auth::user()->role === 'admin_site')
                <p class="text-xs font-semibold text-blue-600">
                    <i class="mr-1 fa-solid fa-building-user"></i> Site Admin: {{ Auth::user()->site->machine_name ?? '-' }}
                </p>
            @endif
        </div>

        <div class="overflow-hidden bg-white shadow-sm ring-1 ring-gray-200 rounded-2xl">
            <div class="p-6 border-b border-gray-100 bg-gray-50/50">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="relative w-full md:w-80">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" name="search" id="search" placeholder="Cari nama / posisi karyawan..."
                            value="{{ request('search') }}"
                            class="block w-full py-2.5 pl-10 pr-3 text-sm border-gray-200 rounded-xl bg-white focus:border-blue-500 focus:ring-blue-500 transition-all outline-none border shadow-sm">
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('employee.create') }}"
                            class="flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-bold text-white transition-all bg-blue-600 rounded-xl hover:bg-blue-700 hover:shadow-lg active:scale-95">
                            <i class="fa-solid fa-user-plus"></i> Add Employee
                        </a>
                    </div>
                </div>
            </div>

            <div id="table-container">
                @include('employee.table', ['employees' => $employees])
            </div>
        </div>
    </div>

    {{-- ============ MODAL POP-UP DETAIL KARYAWAN ============ --}}
    <div id="employeeDetailModal"
        class="fixed inset-0 z-50 items-center justify-center hidden p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="w-full max-w-lg mx-auto overflow-hidden bg-white border border-gray-100 shadow-2xl rounded-2xl">
            <div class="flex items-center justify-between px-6 py-4 text-white bg-gray-900">
                <h5 class="flex items-center gap-2 text-base font-bold">
                    <i class="text-blue-400 fa-solid fa-id-card"></i> Detail Karyawan
                </h5>
                <button type="button" onclick="closeEmployeeModal()"
                    class="text-xl leading-none text-gray-400 hover:text-white">&times;</button>
            </div>

            <div class="p-6 space-y-4">
                <div class="flex items-center gap-4 pb-4 border-b border-gray-100">
                    <div
                        class="flex items-center justify-center w-12 h-12 text-xl font-bold text-blue-600 border border-blue-200 rounded-full bg-blue-50 shrink-0">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900" id="detail_name">-</h3>
                        <p class="text-xs font-semibold text-gray-500" id="detail_position">-</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 text-xs">
                    <div class="p-3 border border-gray-100 rounded-xl bg-gray-50/50">
                        <span class="block mb-1 text-gray-400">Site Location</span>
                        <strong class="text-sm text-gray-800" id="detail_site">-</strong>
                    </div>
                    <div class="p-3 border border-gray-100 rounded-xl bg-gray-50/50">
                        <span class="block mb-1 text-gray-400">Branch</span>
                        <strong class="text-sm text-gray-800" id="detail_branch">-</strong>
                    </div>
                    <div class="p-3 border border-gray-100 rounded-xl bg-gray-50/50">
                        <span class="block mb-1 text-gray-400">Status Kepegawaian</span>
                        <span id="detail_status_badge">-</span>
                    </div>
                    <div class="p-3 border border-gray-100 rounded-xl bg-gray-50/50">
                        <span class="block mb-1 text-gray-400">Masa Kerja (Tenure)</span>
                        <strong class="text-sm text-blue-600" id="detail_tenure">-</strong>
                    </div>
                    <div class="p-3 border border-gray-100 rounded-xl bg-gray-50/50">
                        <span class="block mb-1 text-gray-400">Tanggal Bergabung</span>
                        <strong class="text-sm text-gray-800" id="detail_join_date">-</strong>
                    </div>
                    <div class="p-3 border border-gray-100 rounded-xl bg-gray-50/50">
                        <span class="block mb-1 text-gray-400">Mulai Kontrak</span>
                        <strong class="text-sm text-gray-800" id="detail_contract_start">-</strong>
                    </div>
                </div>
            </div>

            <div class="flex justify-end px-6 py-4 border-t border-gray-100 bg-gray-50">
                <button type="button" onclick="closeEmployeeModal()"
                    class="px-5 py-2 text-xs font-bold text-gray-600 transition-all bg-white border border-gray-300 rounded-xl hover:bg-gray-100">
                    Tutup
                </button>
            </div>
        </div>
    </div>

<script>
    const searchInput = document.getElementById('search');
    const tableContainer = document.getElementById('table-container');
    let delayTimer;

    searchInput.addEventListener('input', function() {
        clearTimeout(delayTimer);

        delayTimer = setTimeout(() => {
            const query = searchInput.value;

            fetch(`{{ route('employee.index') }}?search=${encodeURIComponent(query)}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.text())
                .then(html => {
                    tableContainer.innerHTML = html;
                })
                .catch(error => console.error('Error Fetching', error));
        }, 300);
    });

    function showEmployeeDetail(id) {
        fetch(`/employee/${id}`, {
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(async res => {
                if (!res.ok) {
                    let errMsg = 'Gagal mengambil data karyawan (' + res.status + ')';
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
                    '<span class="px-2.5 py-1 text-xs font-bold text-gray-600 bg-gray-100 rounded-full">' + data
                    .status + '</span>';
                if (data.status === 'Permanent') {
                    statusBadge =
                        '<span class="px-2.5 py-1 text-xs font-bold text-green-700 bg-green-50 border border-green-200 rounded-full">Tetap</span>';
                } else if (data.status === 'Contract') {
                    statusBadge =
                        '<span class="px-2.5 py-1 text-xs font-bold text-blue-700 bg-blue-50 border border-blue-200 rounded-full">Kontrak</span>';
                } else if (data.status === 'Probation') {
                    statusBadge =
                        '<span class="px-2.5 py-1 text-xs font-bold text-yellow-700 bg-yellow-50 border border-yellow-200 rounded-full">Probation</span>';
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
</script>@endsection
