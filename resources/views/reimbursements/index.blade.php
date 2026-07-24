@extends('layout.master')

@section('title', 'Reimbursements')

@section('content')
    <div class="w-full space-y-6">

        {{-- HEADER --}}
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl text-slate-900">
                    {{ $pageTitle ?? 'Reimbursement Claims' }}
                </h1>
                <p class="mt-0.5 text-xs sm:text-sm font-medium text-slate-500">Track and manage operational expense claims.
                </p>
            </div>

            {{-- TOTAL BUDGET CARD --}}
            <div
                class="flex items-center gap-3.5 px-5 py-3 bg-white border border-slate-200/80 shadow-2xs rounded-2xl shrink-0">
                <div class="p-2.5 bg-emerald-50 text-emerald-600 rounded-xl">
                    <i class="text-lg fa-solid fa-money-bill-wave"></i>
                </div>
                <div>
                    <span class="text-[10px] font-bold uppercase text-slate-400 tracking-wider block">Total Approved
                        Funds</span>
                    <span class="text-base font-black sm:text-lg text-slate-800">
                        Rp {{ number_format($totalApprovedAmount ?? 0, 0, ',', '.') }}
                    </span>
                </div>
            </div>
        </div>

        {{-- MAIN CONTAINER --}}
        <div class="overflow-hidden bg-white border shadow-sm border-slate-200/80 rounded-2xl sm:rounded-3xl">
            <div
                class="flex flex-col justify-between gap-4 p-5 border-b lg:flex-row lg:items-center sm:p-6 border-slate-100 bg-slate-50/50">
                <div class="flex flex-col flex-1 gap-3 sm:flex-row sm:items-center">
                    <h3 class="text-base font-extrabold text-slate-800 shrink-0">Claim Logs</h3>

                    {{-- SEARCH BAR --}}
                    <div class="relative w-full sm:w-64">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                            <i class="text-xs fa-solid fa-magnifying-glass"></i>
                        </span>
                        <input type="text" id="reimburseSearchInput" onkeyup="filterReimburseList()"
                            placeholder="Search staff or comment..."
                            class="w-full py-2.5 pl-10 pr-3.5 text-xs font-medium bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 text-slate-700 transition-all">
                    </div>

                    {{-- MONTH FILTER FORM --}}
                    <form method="GET" action="{{ route('reimbursements.index') }}" id="monthFilterForm"
                        class="w-full sm:w-auto">
                        <div class="relative">
                            <select name="month" onchange="document.getElementById('monthFilterForm').submit()"
                                class="w-full sm:w-44 py-2.5 pl-3.5 pr-10 text-xs font-bold bg-amber-50/60 border border-amber-200/80 rounded-xl text-amber-900 focus:outline-none focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 appearance-none cursor-pointer transition-all">
                                <option value="">📅 All Months</option>
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
                                class="absolute inset-y-0 right-0 flex items-center pr-3.5 pointer-events-none text-amber-600">
                                <i class="text-[10px] fa-solid fa-chevron-down"></i>
                            </span>
                        </div>
                    </form>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('reimbursements.create') }}"
                        class="flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-bold text-white transition-all bg-amber-600 rounded-xl hover:bg-amber-700 shadow-md shadow-amber-600/20 active:scale-95">
                        <i class="fa-solid fa-plus"></i>
                        <span>File New Claim</span>
                    </a>
                    <a href="{{ route('reimbursements.export_pdf', ['month' => request('month')]) }}"
                        class="flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-bold text-white transition-all bg-blue-600 rounded-xl hover:bg-blue-700 shadow-md shadow-blue-600/20 active:scale-95">
                        <i class="fas fa-file-pdf"></i> PDF Summary
                    </a>
                    <a href="javascript:void(0)" onclick="exportExcelReport()"
                        class="flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-bold text-white transition-all bg-emerald-600 rounded-xl hover:bg-emerald-700 shadow-md shadow-emerald-600/20 active:scale-95">
                        <i class="fa-solid fa-file-excel"></i> Export Excel
                    </a>
                </div>
            </div>

            {{-- EMPTY SEARCH STATE --}}
            <div id="emptySearchState" class="flex-col items-center justify-center hidden p-12 text-center bg-white">
                <div class="flex items-center justify-center w-12 h-12 mb-3 rounded-xl bg-slate-100 text-slate-400">
                    <i class="text-lg fa-solid fa-receipt"></i>
                </div>
                <h4 class="text-sm font-bold text-slate-700">No Claims Found</h4>
                <p class="max-w-xs mt-1 text-xs text-slate-400">We couldn't find any results matching your search query.</p>
            </div>

            {{-- DESKTOP VIEW --}}
            <div id="desktopTableContainer" class="hidden overflow-x-auto md:block">
                <table class="w-full text-left border-collapse min-w-[750px]">
                    <thead>
                        <tr
                            class="text-[11px] font-extrabold uppercase tracking-wider text-slate-500 bg-slate-100/70 border-b border-slate-200/80">
                            <th class="py-3.5 px-6 text-center w-14">No</th>
                            <th class="px-6 py-3.5">Requester / Date</th>
                            <th class="px-6 py-3.5">Category</th>
                            <th class="px-6 py-3.5">Details / Route</th>
                            <th class="px-6 py-3.5 text-center">Amount</th>
                            <th class="px-6 py-3.5 text-center">Status</th>
                            <th class="px-6 py-3.5 text-right w-48">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-xs font-medium divide-y divide-slate-100 sm:text-sm text-slate-700">
                        @forelse ($reimbursements as $r)
                            @php
                                $reimburseMonth = \Carbon\Carbon::parse($r->date)->format('m');
                            @endphp
                            <tr class="transition-colors hover:bg-slate-50/80 reimburse-row-item"
                                data-search-staff="{{ strtolower($r->person_name) }}"
                                data-search-title="{{ strtolower($r->comment ?? '') }}" data-month="{{ $reimburseMonth }}">
                                <td class="py-3.5 px-6 text-center text-slate-400 font-bold">
                                    {{ $loop->iteration }}
                                </td>

                                <td class="px-6 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex items-center justify-center w-8 h-8 text-xs font-black text-amber-700 bg-amber-50 rounded-xl shrink-0">
                                            {{ strtoupper(substr($r->person_name ?? '?', 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-800">{{ $r->person_name }}</p>
                                            <p class="text-[11px] font-normal text-slate-400">
                                                {{ \Carbon\Carbon::parse($r->date)->format('d M Y') }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-3.5">
                                    <span
                                        class="px-2.5 py-1 text-[10px] font-bold rounded-lg uppercase tracking-wider
                                        {{ $r->category == 'transportation' ? 'bg-blue-50 text-blue-700 border border-blue-200/60' : ($r->category == 'delivery' ? 'bg-purple-50 text-purple-700 border border-purple-200/60' : 'bg-slate-100 text-slate-700 border border-slate-200') }}">
                                        {{ $r->category }}
                                    </span>
                                </td>
                                <td class="px-6 py-3.5">
                                    @if (in_array($r->category, ['transportation', 'delivery']))
                                        <p class="text-xs font-semibold text-slate-600">
                                            <i class="mr-1 fa-solid fa-location-dot text-rose-500"></i>
                                            {{ $r->from_location }}
                                            <i class="mx-1 fa-solid fa-arrow-right text-slate-400"></i>
                                            {{ $r->to_location }}
                                        </p>
                                    @else
                                        <p class="text-xs italic text-slate-400">No routing required</p>
                                    @endif
                                </td>
                                <td class="px-6 py-3.5 text-center">
                                    <span class="text-xs font-black text-slate-800">Rp
                                        {{ number_format($r->amount, 0, ',', '.') }}</span>
                                </td>
                                <td class="px-6 py-3.5 text-center">
                                    @if ($r->status == 'approved')
                                        <span
                                            class="px-2.5 py-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200/60 rounded-lg uppercase">Approved</span>
                                    @elseif($r->status == 'rejected')
                                        <span
                                            class="px-2.5 py-1 text-[10px] font-bold text-rose-700 bg-rose-50 border border-rose-200/60 rounded-lg uppercase">Rejected</span>
                                    @else
                                        <span
                                            class="px-2.5 py-1 text-[10px] font-bold text-amber-700 bg-amber-50 border border-amber-200/60 rounded-lg uppercase animate-pulse">
                                            {{ strtoupper(str_replace('_', ' ', $r->status)) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-3.5 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="{{ route('reimbursements.export_single_pdf', $r->id) }}"
                                            class="p-1.5 text-rose-600 bg-rose-50 hover:bg-rose-600 hover:text-white rounded-lg transition-colors"
                                            title="Download Invoice PDF">
                                            <i class="text-xs fas fa-file-pdf"></i>
                                        </a>

                                        <button onclick="openDetailModal(this)" data-reimbursement="{{ json_encode($r) }}"
                                            class="p-1.5 text-slate-600 bg-slate-100 hover:bg-slate-800 hover:text-white rounded-lg transition-colors"
                                            title="Quick View Details">
                                            <i class="text-xs fa-solid fa-receipt"></i>
                                        </button>

                                        <a href="{{ route('reimbursements.approval', $r->id) }}"
                                            class="p-1.5 text-blue-600 bg-blue-50 hover:bg-blue-600 hover:text-white rounded-lg transition-colors"
                                            title="Digital Signature Page">
                                            <i class="text-xs fa-solid fa-pen-nib"></i>
                                        </a>

                                        <button onclick="confirmCancel('{{ $r->id }}')"
                                            class="p-1.5 text-rose-600 bg-rose-50 hover:bg-rose-600 hover:text-white rounded-lg transition-colors"
                                            title="Cancel Claim">
                                            <i class="text-xs fa-solid fa-ban"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-10 text-center text-slate-400">
                                    <i class="block mb-2 text-3xl opacity-50 fa-solid fa-receipt"></i>
                                    <p class="text-sm font-bold text-slate-700">No reimbursement claims filed for this
                                        month</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- MOBILE VIEW --}}
            <div id="mobileCardContainer" class="p-4 space-y-3.5 md:hidden bg-slate-50/50">
                @forelse ($reimbursements as $r)
                    @php
                        $reimburseMonth = \Carbon\Carbon::parse($r->date)->format('m');
                    @endphp
                    <div class="p-4 space-y-3 bg-white border border-slate-200/80 shadow-2xs rounded-xl reimburse-card-item"
                        data-month="{{ $reimburseMonth }}">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-2.5">
                                <div
                                    class="flex items-center justify-center w-8 h-8 text-xs font-black rounded-lg text-amber-700 bg-amber-50 shrink-0">
                                    {{ strtoupper(substr($r->person_name ?? '?', 0, 1)) }}
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-slate-800">{{ $r->person_name }}</p>
                                    <p class="text-[10px] text-slate-400">
                                        {{ \Carbon\Carbon::parse($r->date)->format('d M Y') }}</p>
                                </div>
                            </div>
                            <div>
                                @if ($r->status == 'approved')
                                    <span
                                        class="px-2 py-0.5 text-[9px] font-bold text-emerald-700 bg-emerald-50 rounded-md uppercase border border-emerald-200/60">Approved</span>
                                @elseif($r->status == 'rejected')
                                    <span
                                        class="px-2 py-0.5 text-[9px] font-bold text-rose-700 bg-rose-50 rounded-md uppercase border border-rose-200/60">Rejected</span>
                                @else
                                    <span
                                        class="px-2 py-0.5 text-[9px] font-bold text-amber-700 bg-amber-50 rounded-md uppercase border border-amber-200/60 animate-pulse">Pending</span>
                                @endif
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2 text-xs bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                            <div>
                                <span class="block text-[10px] font-bold text-slate-400 uppercase">Category</span>
                                <span class="font-bold text-slate-700 uppercase text-[11px]">{{ $r->category }}</span>
                            </div>
                            <div>
                                <span class="block text-[10px] font-bold text-slate-400 uppercase">Amount</span>
                                <span class="font-bold text-slate-800 text-[11px]">Rp
                                    {{ number_format($r->amount, 0, ',', '.') }}</span>
                            </div>
                        </div>

                        <div class="flex gap-2 pt-1">
                            <button onclick="openDetailModal(this)" data-reimbursement="{{ json_encode($r) }}"
                                class="p-2 text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg font-bold text-xs flex-1 text-center flex justify-center items-center gap-1.5 transition-colors">
                                <i class="fa-solid fa-receipt"></i> Details
                            </button>

                            <a href="{{ route('reimbursements.approval', $r->id) }}"
                                class="p-2 text-white bg-amber-600 hover:bg-amber-700 rounded-lg font-bold text-xs flex-1 text-center flex justify-center items-center gap-1.5 shadow-md shadow-amber-600/20 active:scale-95 transition-all">
                                <i class="fa-solid fa-pen-nib"></i> Sign Claim
                            </a>
                        </div>
                    </div>
                @empty
                    <div
                        class="p-8 text-xs font-medium text-center bg-white border border-slate-200 text-slate-400 rounded-xl">
                        No reimbursement claims filed for this month.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- MODAL 1: QUICK DETAIL PREVIEW --}}
    <div id="detailModal"
        class="fixed inset-0 z-50 flex items-center justify-center hidden p-3 transition-all duration-300 sm:p-4 bg-slate-900/60 backdrop-blur-xs">
        <div
            class="relative w-full max-w-4xl bg-white shadow-2xl rounded-2xl sm:rounded-3xl flex flex-col max-h-[90vh] overflow-hidden">
            <div class="flex items-center justify-between p-5 border-b border-slate-100 bg-slate-50/50 shrink-0">
                <div class="space-y-0.5">
                    <span id="modal-category"
                        class="px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wider rounded-md bg-amber-50 text-amber-700 border border-amber-200/60">Category</span>
                    <h3 class="text-base font-extrabold text-slate-800">Operational Claim Specification</h3>
                </div>
                <button onclick="closeDetailModal()"
                    class="p-2 rounded-full text-slate-400 hover:text-slate-600 bg-slate-100">
                    <i class="text-xs fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="grid grid-cols-1 gap-5 p-5 overflow-y-auto text-xs lg:grid-cols-5 sm:text-sm">
                <div class="space-y-4 lg:col-span-2">
                    <div class="grid grid-cols-2 gap-3 p-3.5 border border-slate-200/80 bg-slate-50 rounded-xl">
                        <div>
                            <span
                                class="text-slate-400 font-bold block uppercase text-[10px] tracking-wider">Requester</span>
                            <p id="modal-name" class="font-bold text-slate-800 mt-0.5">-</p>
                        </div>
                        <div>
                            <span class="text-slate-400 font-bold block uppercase text-[10px] tracking-wider">Date
                                Filed</span>
                            <p id="modal-date" class="font-bold text-slate-800 mt-0.5">-</p>
                        </div>
                    </div>

                    <div class="p-3.5 space-y-3 border border-slate-200/80 rounded-xl">
                        <div>
                            <span class="text-slate-400 font-bold block uppercase text-[10px] tracking-wider">Total Claim
                                Value</span>
                            <p id="modal-amount" class="text-lg font-black text-rose-600 mt-0.5">Rp 0</p>
                        </div>
                        <div class="border-t border-slate-100 pt-2.5">
                            <span class="text-slate-400 font-bold block uppercase text-[10px] tracking-wider">Route
                                Info</span>
                            <p id="modal-route" class="font-semibold leading-normal text-slate-700 mt-0.5">-</p>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <span class="text-slate-400 font-bold block uppercase text-[10px] tracking-wider">Statement
                            Description</span>
                        <div class="p-3 text-xs italic font-medium leading-relaxed border bg-amber-50/40 border-amber-200/60 rounded-xl text-slate-600"
                            id="modal-comment">
                            "No description provided."
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <span class="text-slate-400 font-bold block uppercase text-[10px] tracking-wider">
                            <i class="mr-1 fa-solid fa-timeline"></i> Sign & Approval Status
                        </span>
                        <div class="p-3 space-y-2 text-xs border bg-slate-50 rounded-xl border-slate-200/80">
                            <div class="flex items-center justify-between pb-1.5 border-b border-slate-200/60">
                                <span class="font-bold text-slate-600 flex items-center gap-1.5">
                                    <i class="fa-solid fa-user text-[10px] text-slate-400"></i> 1. Staff Requester
                                </span>
                                <span id="sign-status-staff" class="px-2 py-0.5 rounded-md text-[10px] font-bold"></span>
                            </div>
                            <div class="flex items-center justify-between pb-1.5 border-b border-slate-200/60">
                                <span class="font-bold text-slate-600 flex items-center gap-1.5">
                                    <i class="fa-solid fa-user-tie text-[10px] text-slate-400"></i> 2. Team Leader
                                </span>
                                <span id="sign-status-leader" class="px-2 py-0.5 rounded-md text-[10px] font-bold"></span>
                            </div>
                            <div class="flex items-center justify-between pb-1.5 border-b border-slate-200/60">
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

                <div class="lg:col-span-3 flex flex-col space-y-1.5 min-h-[300px]">
                    <span
                        class="text-slate-400 font-bold block uppercase text-[10px] tracking-wider flex items-center gap-1">
                        <i class="fa-solid fa-scroll text-slate-400"></i> Receipt Attachment Preview
                    </span>
                    <div id="modal-attachment-frame"
                        class="relative flex-1 w-full overflow-hidden border shadow-inner bg-slate-100 rounded-2xl border-slate-200/80">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL 2: CANCEL FORM MODAL --}}
    <div id="cancelModal"
        class="fixed inset-0 z-50 flex items-center justify-center hidden p-3 transition-all duration-300 sm:p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="relative w-full max-w-sm p-6 text-center bg-white shadow-2xl rounded-2xl">
            <div class="flex items-center justify-center w-12 h-12 mx-auto mb-3 rounded-xl bg-rose-100 text-rose-600">
                <i class="text-lg fa-solid fa-triangle-exclamation"></i>
            </div>
            <h3 class="text-base font-extrabold text-slate-800">Cancel Reimbursement Claim?</h3>
            <p class="mt-1 text-xs text-slate-500">This will permanently delete this operational expense file record from
                the database.</p>
            <form method="POST" action="" class="flex gap-2.5 mt-5">
                @csrf @method('DELETE')
                <button type="submit"
                    class="flex-1 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs uppercase tracking-wider rounded-xl transition-all active:scale-95 shadow-md shadow-rose-600/20">Yes,
                    Delete</button>
                <button type="button" onclick="document.getElementById('cancelModal').classList.add('hidden')"
                    class="flex-1 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs uppercase tracking-wider rounded-xl transition-colors">Dismiss</button>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function filterReimburseList() {
            const filterText = document.getElementById('reimburseSearchInput').value.toLowerCase();
            const urlParams = new URLSearchParams(window.location.search);
            const selectedMonth = urlParams.get('month');

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

        function exportExcelReport() {
            const url = new URL('{{ route('reimbursements.export_excel') }}');
            const urlParams = new URLSearchParams(window.location.search);
            const currentMonth = urlParams.get('month');

            if (currentMonth) {
                url.searchParams.set('month', currentMonth);
            }

            @if (Auth::user()->role === 'superadmin')
                url.searchParams.set('all_site', '1');
            @endif

            window.location.href = url.href;
        }

        function openDetailModal(buttonElement) {
            const data = JSON.parse(buttonElement.getAttribute('data-reimbursement'));

            document.getElementById('modal-name').innerText = data.person_name;
            document.getElementById('modal-category').innerText = data.category;
            document.getElementById('modal-comment').innerText = data.comment ? `"${data.comment}"` :
                "No description provided.";

            const dateObj = new Date(data.date);
            document.getElementById('modal-date').innerText = dateObj.toLocaleDateString('en-US', {
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

            function renderSignBadge(elementId, isSigned, fallbackText = "Unsigned") {
                const el = document.getElementById(elementId);
                if (isSigned) {
                    el.innerText = "✓ Signed";
                    el.className =
                        "px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/60";
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

            renderSignBadge('sign-status-staff', hasStaff, "Pending Sign");
            renderSignBadge('sign-status-leader', hasLeader, "Pending Review");
            renderSignBadge('sign-status-station', hasStation, "Pending Approval");
            renderSignBadge('sign-status-manager', hasManager, "Pending Disbursement");

            if (data.status === 'rejected') {
                if (!hasLeader) renderSignBadge('sign-status-leader', false, "✖ Rejected / Cancelled");
                else if (!hasStation) renderSignBadge('sign-status-station', false, "✖ Rejected by Station Master");
                else if (!hasManager) renderSignBadge('sign-status-manager', false, "✖ Rejected by Manager");
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

            const m = document.getElementById('detailModal');
            m.classList.remove('hidden');
            m.classList.add('flex');
        }

        function closeDetailModal() {
            const m = document.getElementById('detailModal');
            m.classList.add('hidden');
            m.classList.remove('flex');
        }

        function confirmCancel(id) {
            const modal = document.getElementById('cancelModal');
            if (!modal) return;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            const form = modal.querySelector('form');
            if (form) form.action = `/reimbursements/${id}`;
        }
    </script>
@endpush
