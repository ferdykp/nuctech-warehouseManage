@extends('layout.master')

@section('title', 'Reimbursements')

@section('content')
    <div class="w-full px-6 py-8 pb-10 space-y-6 md:space-y-8">
        {{-- HEADER --}}
        <div class="flex flex-col gap-6 px-2 lg:flex-row lg:items-center lg:justify-between">
            <div class="space-y-1">
                <h2 class="text-3xl font-black tracking-tighter md:text-4xl text-slate-800">
                    {{ $pageTitle ?? 'Reimbursement Claims' }}</h2>
                <p class="text-xs font-medium md:text-sm text-slate-500">Track and manage employee operational expenses.</p>
            </div>

            {{-- ACCUMULATED TOTAL BUDGET CARD --}}
            <div class="flex items-center gap-4 px-6 py-4 bg-white border shadow-sm border-slate-100 rounded-2xl">
                <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl">
                    <i class="text-xl fa-solid fa-money-bill-wave"></i>
                </div>
                <div>
                    <span class="text-[9px] font-black uppercase text-slate-400 tracking-widest block">Total Approved
                        Funds</span>
                    <span class="text-lg font-black text-slate-800">Rp
                        {{ number_format($totalApprovedAmount ?? 0, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        {{-- MAIN CONTAINER --}}
        <div class="bg-white border border-slate-100 shadow-sm rounded-[2rem] md:rounded-[3rem] overflow-hidden">
            <div
                class="flex flex-col items-stretch justify-between gap-4 p-6 border-b lg:flex-row lg:items-center border-slate-50">
                <div class="flex flex-col flex-1 gap-3 sm:flex-row sm:items-center">
                    <h3 class="text-lg font-bold text-slate-800 shrink-0">Claim Logs</h3>

                    {{-- SEARCH BAR --}}
                    <div class="relative w-full max-w-md">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-slate-400">
                            <i class="text-sm fa-solid fa-magnifying-glass"></i>
                        </span>
                        <input type="text" id="reimburseSearchInput" onkeyup="filterReimburseList()"
                            placeholder="Search by staff name or comment..."
                            class="w-full py-2.5 pl-11 pr-4 text-xs font-medium bg-slate-50 border border-slate-100 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 text-slate-700 placeholder-slate-400 transition-all">
                    </div>

                    {{-- FORM FILTER BULAN (Sinkronisasi Otomatis) --}}
                    <form method="GET" action="{{ route('reimbursements.index') }}" id="monthFilterForm"
                        class="w-full sm:w-auto">
                        <div class="relative">
                            <select name="month" onchange="document.getElementById('monthFilterForm').submit()"
                                class="w-full sm:w-48 py-2.5 pl-4 pr-10 text-xs font-bold bg-amber-50/50 border border-amber-200/60 rounded-xl text-amber-900 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 appearance-none cursor-pointer transition-all">
                                <option value="">📅 All Months (Semua)</option>
                                @foreach ([
            '01' => 'January',
            '02' => 'February',
            '03' => 'March',
            '04' => 'April',
            '05' => 'May',
            '06' => 'June',
            '07' => 'July',
            '08' => 'August',
            '09' => 'September',
            '10' => 'October',
            '11' => 'November',
            '12' => 'December',
        ] as $value => $name)
                                    <option value="{{ $value }}" {{ request('month') == $value ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                            <span
                                class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-amber-600">
                                <i class="text-[10px] fa-solid fa-chevron-down"></i>
                            </span>
                        </div>
                    </form>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('reimbursements.create') }}"
                        class="flex items-center justify-center gap-2 px-6 py-3 text-sm font-black text-white transition-all shadow-lg bg-amber-600 rounded-xl shadow-amber-600/10 active:scale-95">
                        <i class="fa-solid fa-plus"></i>
                        <span>File New Claim</span>
                    </a>
                    <a href="{{ route('reimbursements.export_pdf', ['month' => request('month')]) }}"
                        class="flex items-center justify-center gap-2 px-6 py-3 text-sm font-black text-white transition-all bg-blue-400 shadow-lg rounded-xl shadow-amber-600/10 active:scale-95">
                        <i class="fas fa-file-pdf"></i> Export Invoice PDF
                    </a>

                    <div class="flex items-center gap-3">
                        <a href="javascript:void(0)" onclick="exportExcelReport()"
                            class="inline-flex items-center gap-2 px-6 py-3 text-sm font-bold text-white transition-all shadow-xl bg-emerald-600 rounded-2xl hover:bg-emerald-700 shadow-emerald-200 active:scale-95">
                            <i class="fa-solid fa-file-excel"></i>
                            Export Excel
                        </a>
                    </div>
                </div>
            </div>

            {{-- EMPTY DATA ALERT --}}
            <div id="emptySearchState" class="flex-col items-center justify-center hidden p-12 text-center bg-white">
                <div class="flex items-center justify-center w-16 h-16 mb-4 rounded-full bg-slate-50 text-slate-400">
                    <i class="text-xl fa-solid fa-receipt"></i>
                </div>
                <h4 class="text-sm font-bold text-slate-700">No Claims Found</h4>
                <p class="max-w-xs mt-1 text-xs text-slate-400">We couldn't find any results matching your search terms.</p>
            </div>

            {{-- DESKTOP VIEW --}}
            <div id="desktopTableContainer" class="hidden overflow-x-auto md:block">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-[10px] font-black tracking-[0.15em] text-slate-400 uppercase bg-slate-50/50">
                            <th class="px-8 py-5">Person Name / Date</th>
                            <th class="px-6 py-5">Category</th>
                            <th class="px-6 py-5">Details / Route</th>
                            <th class="px-6 py-5 text-center">Amount</th>
                            <th class="px-6 py-5 text-center">Status</th>
                            <th class="px-8 py-5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse ($reimbursements as $r)
                            @php
                                $reimburseMonth = \Carbon\Carbon::parse($r->date)->format('m');
                            @endphp
                            <tr class="transition-colors group hover:bg-slate-50/50 reimburse-row-item"
                                data-search-staff="{{ strtolower($r->person_name) }}"
                                data-search-title="{{ strtolower($r->comment ?? '') }}" data-month="{{ $reimburseMonth }}">
                                <td class="px-8 py-5">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex items-center justify-center font-black w-9 h-9 text-amber-600 bg-amber-50 rounded-xl shrink-0">
                                            {{ strtoupper(substr($r->person_name ?? '?', 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-slate-800">{{ $r->person_name }}</p>
                                            <p class="text-[10px] font-semibold text-slate-400">
                                                {{ \Carbon\Carbon::parse($r->date)->format('d M Y') }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-5">
                                    <span
                                        class="px-2.5 py-1 text-[10px] font-bold rounded-lg uppercase tracking-wider
                                        {{ $r->category == 'transportation' ? 'bg-blue-50 text-blue-700' : ($r->category == 'delivery' ? 'bg-purple-50 text-purple-700' : 'bg-slate-100 text-slate-700') }}">
                                        {{ $r->category }}
                                    </span>
                                </td>
                                <td class="px-6 py-5">
                                    @if (in_array($r->category, ['transportation', 'delivery']))
                                        <p class="text-xs font-semibold text-slate-600">
                                            <i class="mr-1 fa-solid fa-location-dot text-rose-500"></i>
                                            {{ $r->from_location }}
                                            <i class="mx-1 fa-solid fa-arrow-right text-slate-400"></i>
                                            {{ $r->to_location }}
                                        </p>
                                    @else
                                        <p class="text-xs italic text-slate-400">No routing needed</p>
                                    @endif
                                </td>
                                <td class="px-6 py-5 text-center">
                                    <span class="text-xs font-black text-slate-700">Rp
                                        {{ number_format($r->amount, 0, ',', '.') }}</span>
                                </td>
                                <td class="px-6 py-5 text-center">
                                    @if ($r->status == 'approved')
                                        <span
                                            class="px-3 py-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 rounded-full">Approved</span>
                                    @elseif($r->status == 'rejected')
                                        <span
                                            class="px-3 py-1 text-[10px] font-bold text-rose-700 bg-rose-50 rounded-full">Rejected</span>
                                    @else
                                        <span
                                            class="px-3 py-1 text-[10px] font-bold text-amber-700 bg-amber-50 rounded-full animate-pulse">{{ strtoupper(str_replace('_', ' ', $r->status)) }}</span>
                                    @endif
                                </td>
                                <td class="px-8 py-5">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="{{ route('reimbursements.export_single_pdf', $r->id) }}"
                                            class="p-2 transition-colors rounded-lg text-rose-600 bg-rose-50 hover:bg-rose-600 hover:text-white"
                                            title="Download Invoice Layout">
                                            <i class="text-xs fas fa-file-pdf"></i>
                                        </a>

                                        <button onclick="openDetailModal(this)" data-reimbursement="{{ json_encode($r) }}"
                                            class="p-2 transition-colors rounded-lg text-slate-600 bg-slate-100 hover:bg-slate-200"
                                            title="Quick View Specs">
                                            <i class="text-xs fa-solid fa-receipt"></i>
                                        </button>

                                        <a href="{{ route('reimbursements.approval', $r->id) }}"
                                            class="p-2 text-blue-600 transition-colors rounded-lg bg-blue-50 hover:bg-blue-600 hover:text-white"
                                            title="Go to Digital Sign Page">
                                            <i class="text-xs fa-solid fa-pen-nib"></i>
                                        </a>

                                        <button onclick="confirmCancel('{{ $r->id }}')"
                                            class="p-2 transition-colors rounded-lg text-rose-600 bg-rose-50 hover:bg-rose-600 hover:text-white"
                                            title="Cancel Claim">
                                            <i class="text-xs fa-solid fa-ban"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-12 text-xs font-semibold text-center bg-white text-slate-400">
                                    Tidak ada pengajuan klaim pada bulan yang dipilih.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- MOBILE VIEW --}}
            <div id="mobileCardContainer" class="p-4 space-y-4 md:hidden bg-slate-50/50">
                @forelse ($reimbursements as $r)
                    @php
                        $reimburseMonth = \Carbon\Carbon::parse($r->date)->format('m');
                    @endphp
                    <div class="p-5 space-y-4 bg-white border shadow-sm border-slate-100 rounded-2xl reimburse-card-item"
                        data-month="{{ $reimburseMonth }}">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex items-center justify-center w-10 h-10 font-black text-amber-600 bg-amber-50 rounded-xl">
                                    {{ strtoupper(substr($r->person_name ?? '?', 0, 1)) }}
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-slate-800">{{ $r->person_name }}</p>
                                    <p class="text-[10px] font-semibold text-slate-400">
                                        {{ \Carbon\Carbon::parse($r->date)->format('d M Y') }}</p>
                                </div>
                            </div>
                            <div>
                                @if ($r->status == 'approved')
                                    <span
                                        class="px-2.5 py-1 text-[9px] font-bold text-emerald-700 bg-emerald-50 rounded-full">Approved</span>
                                @elseif($r->status == 'rejected')
                                    <span
                                        class="px-2.5 py-1 text-[9px] font-bold text-rose-700 bg-rose-50 rounded-full">Rejected</span>
                                @else
                                    <span
                                        class="px-2.5 py-1 text-[9px] font-bold text-amber-700 bg-amber-50 rounded-full animate-pulse">Pending</span>
                                @endif
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2 text-[11px] bg-slate-50 p-3 rounded-xl border border-slate-100">
                            <div>
                                <span class="block font-semibold text-slate-400">Category</span>
                                <span class="font-bold uppercase text-slate-700">{{ $r->category }}</span>
                            </div>
                            <div>
                                <span class="block font-semibold text-slate-400">Amount</span>
                                <span class="font-bold text-slate-800">Rp
                                    {{ number_format($r->amount, 0, ',', '.') }}</span>
                            </div>
                        </div>

                        <div class="flex gap-2 pt-1">
                            <button onclick="openDetailModal(this)" data-reimbursement="{{ json_encode($r) }}"
                                class="p-2.5 text-slate-600 bg-slate-100 rounded-xl font-bold text-xs flex-1 text-center flex justify-center items-center gap-1.5">
                                <i class="fa-solid fa-receipt"></i> Details
                            </button>

                            <a href="{{ route('reimbursements.approval', $r->id) }}"
                                class="p-2.5 text-white bg-amber-600 hover:bg-amber-700 rounded-xl font-black text-xs flex-1 text-center flex justify-center items-center gap-1.5 shadow-md shadow-amber-600/10">
                                <i class="fa-solid fa-pen-nib"></i> Sign / TTD
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-xs font-semibold text-center bg-white border text-slate-400 rounded-2xl">Tidak ada
                        pengajuan klaim pada bulan yang dipilih.</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- MODAL 1: QUICK DETAIL PREVIEW (READ-ONLY) --}}
    <div id="detailModal" class="fixed inset-0 z-[9999] flex items-center justify-center hidden px-4 m-0">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeDetailModal()"></div>

        <div
            class="relative w-full max-w-5xl p-6 md:p-8 bg-white shadow-2xl rounded-[2.5rem] flex flex-col h-[85vh] overflow-hidden">
            <button onclick="closeDetailModal()"
                class="absolute z-50 flex items-center justify-center w-8 h-8 transition-colors rounded-full top-6 right-6 bg-slate-50 hover:bg-slate-100 text-slate-400 hover:text-slate-600">
                <i class="text-xs fa-solid fa-xmark"></i>
            </button>

            <div class="pr-10 mb-5 space-y-1">
                <span id="modal-category"
                    class="px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-md bg-amber-50 text-amber-700 border border-amber-100/50">Category</span>
                <h3 class="mt-1 text-xl font-black tracking-tight text-slate-800">Operational Claim Specification</h3>
            </div>

            <div class="grid flex-1 grid-cols-1 gap-6 pb-2 pr-1 overflow-y-auto lg:grid-cols-5">
                <div class="flex flex-col justify-between h-full space-y-5 lg:col-span-2">
                    <div class="space-y-5">
                        <div
                            class="grid grid-cols-1 gap-4 p-5 text-xs border bg-slate-50 border-slate-100 rounded-2xl sm:grid-cols-2">
                            <div class="space-y-0.5">
                                <span class="text-slate-400 font-bold block uppercase text-[9px] tracking-wide">Staff
                                    Requester</span>
                                <p id="modal-name" class="text-sm font-bold text-slate-800">-</p>
                            </div>
                            <div class="space-y-0.5">
                                <span class="text-slate-400 font-bold block uppercase text-[9px] tracking-wide">Date
                                    Filed</span>
                                <p id="modal-date" class="text-sm font-bold text-slate-800">-</p>
                            </div>
                        </div>

                        <div class="p-5 space-y-4 text-xs border border-slate-100 rounded-2xl">
                            <div class="space-y-0.5">
                                <span class="text-slate-400 font-bold block uppercase text-[9px] tracking-wide">Total Value
                                    Request</span>
                                <p id="modal-amount" class="text-xl font-black text-rose-600">Rp 0</p>
                            </div>
                            <div class="border-t border-slate-50 pt-3 space-y-0.5">
                                <span class="text-slate-400 font-bold block uppercase text-[9px] tracking-wide">Route Log /
                                    Travel Info</span>
                                <p id="modal-route" class="font-bold leading-normal text-slate-700">-</p>
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <span class="text-slate-400 font-bold block uppercase text-[9px] tracking-wide px-1">Statement
                                Description</span>
                            <div class="p-4 text-xs italic font-medium leading-relaxed border bg-amber-50/40 border-amber-100/40 rounded-2xl text-slate-600"
                                id="modal-comment">
                                "No description provided."
                            </div>
                        </div>
                    </div>

                    <div class="mt-2 space-y-2">
                        <span class="text-slate-400 font-bold block uppercase text-[9px] tracking-wide px-1">
                            <i class="mr-1 fa-solid fa-timeline"></i> Processing Sign & Approval Status
                        </span>
                        <div class="p-4 space-y-3 text-xs border bg-slate-50 rounded-2xl border-slate-100">
                            <div class="flex items-center justify-between pb-2 border-b border-slate-100/60">
                                <span class="font-bold text-slate-600 flex items-center gap-1.5">
                                    <i class="fa-solid fa-user text-[10px] text-slate-400"></i> 1. Staff Requester
                                </span>
                                <span id="sign-status-staff" class="px-2 py-0.5 rounded-md text-[10px] font-bold"></span>
                            </div>
                            <div class="flex items-center justify-between pb-2 border-b border-slate-100/60">
                                <span class="font-bold text-slate-600 flex items-center gap-1.5">
                                    <i class="fa-solid fa-user-tie text-[10px] text-slate-400"></i> 2. Team Leader
                                </span>
                                <span id="sign-status-leader" class="px-2 py-0.5 rounded-md text-[10px] font-bold"></span>
                            </div>
                            <div class="flex items-center justify-between pb-2 border-b border-slate-100/60">
                                <span class="font-bold text-slate-600 flex items-center gap-1.5">
                                    <i class="fa-solid fa-house-laptop text-[10px] text-slate-400"></i> 3. Station Master
                                </span>
                                <span id="sign-status-station"
                                    class="px-2 py-0.5 rounded-md text-[10px] font-bold"></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-600 flex items-center gap-1.5">
                                    <i class="fa-solid fa-user-gear text-[10px] text-slate-400"></i> 4. Operational Manager
                                </span>
                                <span id="sign-status-manager"
                                    class="px-2 py-0.5 rounded-md text-[10px] font-bold"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-3 flex flex-col space-y-2 h-full min-h-[380px] lg:min-h-0">
                    <span
                        class="text-slate-400 font-bold block uppercase text-[9px] tracking-wide px-1 flex items-center gap-1">
                        <i class="fa-solid fa-scroll text-slate-400"></i> Invoice Frame Attachment Preview
                    </span>
                    <div id="modal-attachment-frame"
                        class="relative flex-1 w-full overflow-hidden border shadow-inner bg-slate-100 rounded-3xl border-slate-200">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL 2: CANCEL FORM MODAL --}}
    <div id="cancelModal" class="fixed inset-0 z-[9999] flex items-center justify-center hidden px-4">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
            onclick="document.getElementById('cancelModal').classList.add('hidden')"></div>
        <div class="relative w-full max-w-sm p-6 bg-white shadow-2xl rounded-[2.5rem] text-center">
            <div class="flex items-center justify-center w-12 h-12 mx-auto mb-3 rounded-full bg-rose-50 text-rose-500">
                <i class="text-lg fa-solid fa-triangle-exclamation"></i>
            </div>
            <h3 class="text-base font-black text-slate-800">Cancel Reimbursement Claim?</h3>
            <p class="mt-1 text-xs text-slate-400">This will permanently delete this operational file record from database
                servers.</p>
            <form method="POST" action="" class="flex gap-2 mt-4">
                @csrf @method('DELETE')
                <button type="submit"
                    class="flex-1 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs uppercase tracking-wider rounded-xl transition-all shadow-md">Yes,
                    Cancel</button>
                <button type="button" onclick="document.getElementById('cancelModal').classList.add('hidden')"
                    class="flex-1 py-2.5 bg-slate-50 hover:bg-slate-100 text-slate-400 font-bold text-xs uppercase tracking-wider rounded-xl transition-all">Dismiss</button>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // ---------- FILTER LIVE PENCARIAN & DROP-DOWN BULAN KLIEN ----------
        function filterReimburseList() {
            const filterText = document.getElementById('reimburseSearchInput').value.toLowerCase();
            const urlParams = new URLSearchParams(window.location.search);
            const selectedMonth = urlParams.get('month');

            // 1. Desktop Row Filter
            const rows = document.querySelectorAll('.reimburse-row-item');
            let hasDesktopResults = false;
            rows.forEach(row => {
                const staff = row.getAttribute('data-search-staff') || '';
                const title = row.getAttribute('data-search-title') || '';
                const rowMonth = row.getAttribute('data-month') || '';

                const matchesText = staff.includes(filterText) || title.includes(filterText);
                const matchesMonth = !selectedMonth || rowMonth === selectedMonth;

                if (matchesText && matchesMonth) {
                    row.style.display = "";
                    hasDesktopResults = true;
                } else {
                    row.style.display = "none";
                }
            });

            // 2. Mobile Card Filter
            const cards = document.querySelectorAll('.reimburse-card-item');
            let hasMobileResults = false;
            cards.forEach(card => {
                const textContent = card.innerText.toLowerCase();
                const cardMonth = card.getAttribute('data-month') || '';

                const matchesText = textContent.includes(filterText);
                const matchesMonth = !selectedMonth || cardMonth === selectedMonth;

                if (matchesText && matchesMonth) {
                    card.style.display = "";
                    hasMobileResults = true;
                } else {
                    card.style.display = "none";
                }
            });

            const emptyState = document.getElementById('emptySearchState');
            if (emptyState) {
                const isMobile = window.innerWidth < 768;
                const noResults = isMobile ? !hasMobileResults : !hasDesktopResults;
                emptyState.classList.toggle('hidden', !noResults);
                emptyState.classList.toggle('flex', noResults);
            }
        }

        // ---------- LOGIKA EXPORT EXCEL ----------
        function exportExcelReport() {
            const url = new URL('{{ route('reimbursements.export_excel') }}');
            const urlParams = new URLSearchParams(window.location.search);
            const currentMonth = urlParams.get('month');

            if (currentMonth) {
                url.searchParams.set('month', currentMonth);
            }

            window.location.href = url.href;
        }

        // ---------- LOGIKA MODAL QUICK PREVIEW DATA ----------
        function openDetailModal(buttonElement) {
            const data = JSON.parse(buttonElement.getAttribute('data-reimbursement'));

            document.getElementById('modal-name').innerText = data.person_name;
            document.getElementById('modal-category').innerText = data.category;
            document.getElementById('modal-comment').innerText = data.comment ? `"${data.comment}"` :
                "No description provided.";

            const dateObj = new Date(data.date);
            document.getElementById('modal-date').innerText = dateObj.toLocaleDateString('id-ID', {
                day: '2-digit',
                month: 'short',
                year: 'numeric'
            });

            document.getElementById('modal-amount').innerText = new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(data.amount);

            if (data.category === 'transportation' || data.category === 'delivery') {
                document.getElementById('modal-route').innerHTML =
                    `<i class="mr-1 fa-solid fa-map-pin text-rose-500"></i> ${data.from_location} <i class="mx-1 fa-solid fa-arrow-right text-slate-300"></i> ${data.to_location}`;
            } else {
                document.getElementById('modal-route').innerText = "Routing Exempted";
            }

            let signatures = [];
            if (data.signatures_json) {
                try {
                    signatures = typeof data.signatures_json === 'string' ? JSON.parse(data.signatures_json) : data
                        .signatures_json;
                } catch (e) {
                    signatures = [];
                }
            }

            function renderSignBadge(elementId, isSigned, fallbackText = "Belum Ditandatangani") {
                const el = document.getElementById(elementId);
                if (isSigned) {
                    el.innerText = "✓ Sudah Ditandatangani";
                    el.className =
                        "px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200";
                } else {
                    el.innerText = fallbackText;
                    el.className =
                        "px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-50 text-slate-400 border border-slate-200 italic";
                }
            }

            const hasStaff = signatures.some(s => s.role === 'admin_site' || s.level === 'admin_site') || !!data
                .person_name;
            const hasLeader = signatures.some(s => s.role === 'leader' || s.level === 'leader') || (data.status !==
                'pending' && data.status !== 'pending_leader');
            const hasStation = signatures.some(s => s.role === 'station_master' || s.role === 'station') || (data.status ===
                'approved' || data.status === 'pending_manager');
            const hasManager = signatures.some(s => s.role === 'manager') || data.status === 'approved';

            renderSignBadge('sign-status-staff', hasStaff, "Belum TTD");
            renderSignBadge('sign-status-leader', hasLeader, "Belum Direview / TTD");
            renderSignBadge('sign-status-station', hasStation, "Belum Disetujui");
            renderSignBadge('sign-status-manager', hasManager, "Belum Dicairkan");

            if (data.status === 'rejected') {
                if (!hasLeader) renderSignBadge('sign-status-leader', false, "✖ Ditolak / Cancelled");
                else if (!hasStation) renderSignBadge('sign-status-station', false, "✖ Ditolak di Station Master");
                else if (!hasManager) renderSignBadge('sign-status-manager', false, "✖ Ditolak oleh Manager");
            }

            const frame = document.getElementById('modal-attachment-frame');
            frame.innerHTML = '';

            if (data.receipt_attachment) {
                const fileExt = data.receipt_attachment.split('.').pop().toLowerCase();
                const fullUrl = `/storage/${data.receipt_attachment}`;

                if (fileExt === 'pdf') {
                    frame.innerHTML =
                        `<object data="${fullUrl}#toolbar=0" type="application/pdf" class="block w-full h-full"></object>`;
                } else {
                    frame.innerHTML =
                        `<div class="flex items-center justify-center w-full h-full p-2 bg-slate-50"><img src="${fullUrl}" class="object-contain max-w-full max-h-full rounded-lg" /></div>`;
                }
            } else {
                frame.innerHTML =
                    `<div class="flex items-center justify-center w-full h-full text-xs italic text-slate-400">No receipt document proof attached.</div>`;
            }

            document.getElementById('detailModal').classList.remove('hidden');
        }

        function closeDetailModal() {
            document.getElementById('detailModal').classList.add('hidden');
        }

        function confirmCancel(id) {
            const modal = document.getElementById('cancelModal');
            if (!modal) return;
            modal.classList.remove('hidden');
            const form = modal.querySelector('form');
            if (form) form.action = `/reimbursements/${id}`;
        }
    </script>
@endpush
