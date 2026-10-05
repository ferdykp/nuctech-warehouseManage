@extends('layout.master')

@section('title', 'Reimbursement Claims')

@section('content')
    <div class="w-full space-y-5">

        {{-- 1. HEADER & METRIC SUMMARY --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
                    {{ $pageTitle ?? 'Reimbursement Claims' }}
                </h1>
                <p class="mt-0.5 text-xs text-slate-500 sm:text-sm">
                    Manage, audit, and verify operational expense claims across site units.
                </p>
            </div>

            <div class="flex items-center gap-2.5">
                <a href="{{ route('reimbursements.trash') }}"
                    class="inline-flex items-center h-10 gap-2 px-4 text-xs font-semibold transition-all bg-white border text-slate-700 border-slate-200 rounded-xl hover:bg-slate-50 shadow-2xs">
                    <i class="fa-solid fa-box-archive text-slate-400"></i>
                    <span>Recycle Bin</span>
                </a>
                <a href="{{ route('reimbursements.create') }}"
                    class="inline-flex items-center gap-2 h-10 p-4 text-xs font-semibold text-white bg-slate-900 rounded-xl hover:bg-slate-800 transition-all shadow-sm active:scale-[0.98]">
                    <i class="fa-solid fa-plus text-[11px]"></i>
                    <span>File New Claim</span>
                </a>
            </div>
        </div>

        {{-- KPI STATS CARDS --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="flex items-center justify-between p-4 bg-white border border-slate-200/90 rounded-2xl shadow-2xs">
                <div>
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 block">Total
                        Approved</span>
                    <p id="totalApprovedAmountText" class="text-lg font-bold text-slate-900 font-mono mt-0.5">
                        Rp {{ number_format((float) ($totalApprovedAmount ?? 0), 0, ',', '.') }}
                    </p>
                </div>
                <div
                    class="flex items-center justify-center w-10 h-10 border rounded-xl bg-emerald-50 text-emerald-600 shrink-0 border-emerald-100">
                    <i class="text-sm fa-solid fa-wallet"></i>
                </div>
            </div>

            <div class="flex items-center justify-between p-4 bg-white border border-slate-200/90 rounded-2xl shadow-2xs">
                <div>
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 block">Total
                        Records</span>
                    <p class="text-lg font-bold text-slate-900 font-mono mt-0.5">
                        {{ $reimbursements->total() }} <span
                            class="font-sans text-xs font-normal text-slate-400">Claims</span>
                    </p>
                </div>
                <div
                    class="flex items-center justify-center w-10 h-10 border rounded-xl bg-slate-100 text-slate-600 shrink-0 border-slate-200">
                    <i class="text-sm fa-solid fa-receipt"></i>
                </div>
            </div>

            <div class="flex items-center justify-between p-4 bg-white border border-slate-200/90 rounded-2xl shadow-2xs">
                <div>
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 block">Filter
                        Period</span>
                    <p class="mt-1 text-xs font-semibold truncate text-slate-800">
                        {{ request('month') && isset($months[request('month')]) ? $months[request('month')] : 'All Months' }}
                    </p>
                </div>
                <div
                    class="flex items-center justify-center w-10 h-10 border rounded-xl bg-slate-100 text-slate-600 shrink-0 border-slate-200">
                    <i class="text-sm fa-regular fa-calendar-check"></i>
                </div>
            </div>
        </div>

        {{-- 2. TABLE CONTAINER --}}
        <div class="overflow-hidden bg-white border border-slate-200/90 rounded-2xl shadow-2xs">

            {{-- TOOLBAR (FILTER & EXPORT BAR) --}}
            <div
                class="p-4 border-b border-slate-100 bg-slate-50/40 flex flex-col gap-3.5 sm:flex-row sm:items-center sm:justify-between">
                <form id="reimburseFilterForm" action="{{ route('reimbursements.index') }}" method="GET"
                    onsubmit="return false;" class="flex flex-wrap items-center gap-2.5 flex-1">

                    {{-- Search Input --}}
                    <div class="relative w-full sm:w-80">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                            <i class="text-xs fa-solid fa-magnifying-glass"></i>
                        </span>
                        <input type="text" id="reimburseSearchInput" name="search" value="{{ request('search') }}"
                            placeholder="Search requester, route, or invoice ref..." autocomplete="off"
                            class="w-full h-10 pl-9.5 pr-8 text-xs bg-white border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900 transition-all shadow-2xs">

                        <button type="button" id="clearSearchBtn" onclick="clearSearch()"
                            class="{{ request('search') ? '' : 'hidden' }} absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600 cursor-pointer">
                            <i class="text-xs fa-solid fa-circle-xmark"></i>
                        </button>
                    </div>

                    {{-- Month Dropdown --}}
                    <div class="relative w-full sm:w-44">
                        <select id="reimburseMonthSelect" name="month"
                            class="w-full h-10 pl-3.5 pr-8 text-xs bg-white border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900 appearance-none cursor-pointer transition-all shadow-2xs">
                            <option value="">All Months</option>
                            @php
                                $months = [
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
                                ];
                            @endphp
                            @foreach ($months as $value => $name)
                                <option value="{{ $value }}" {{ request('month') == $value ? 'selected' : '' }}>
                                    {{ $name }}
                                </option>
                            @endforeach
                        </select>
                        <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-slate-400">
                            <i class="fa-solid fa-chevron-down text-[10px]"></i>
                        </span>
                    </div>

                </form>

                {{-- Export Actions --}}
                <div class="flex items-center gap-2 shrink-0">
                    <a href="{{ route('reimbursements.export_pdf', ['month' => request('month')]) }}" id="pdfExportLink"
                        download title="Export PDF"
                        class="h-10 px-3.5 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-all shadow-2xs inline-flex items-center gap-2 active:scale-[0.98]">
                        <i class="text-sm fa-solid fa-file-pdf text-rose-500"></i>
                        <span>Export PDF</span>
                    </a>
                    <button type="button" onclick="exportExcelReport()" title="Export Excel"
                        class="h-10 px-3.5 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-all shadow-2xs inline-flex items-center gap-2 active:scale-[0.98] cursor-pointer">
                        <i class="text-sm fa-solid fa-file-excel text-emerald-600"></i>
                        <span>Export Excel</span>
                    </button>
                </div>
            </div>

            {{-- DYNAMIC DATA WRAPPER --}}
            <div id="reimbursementDataWrapper" class="transition-opacity duration-150">

                {{-- DESKTOP TABLE --}}
                <div id="desktopTableContainer" class="hidden overflow-x-auto md:block">
                    <table class="w-full text-left border-collapse min-w-[700px]">
                        <thead>
                            <tr
                                class="text-[11px] font-bold text-slate-400 uppercase tracking-wider bg-slate-50/50 border-b border-slate-100">
                                <th class="px-5 py-3">Requester & Category</th>
                                <th class="px-5 py-3 text-right">Amount</th>
                                <th class="px-5 py-3 text-center">Status</th>
                                <th class="w-48 px-5 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="text-xs font-medium divide-y divide-slate-100 text-slate-700">
                            @forelse ($reimbursements as $r)
                                <tr class="transition-colors hover:bg-slate-50/80">
                                    {{-- Column 1: Requester, Category, Date, & Route/Invoice Ref --}}
                                    <td class="px-5 py-3.5">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="flex items-center justify-center w-8 h-8 text-xs font-bold border text-slate-700 bg-slate-100 border-slate-200 rounded-xl shrink-0">
                                                {{ strtoupper(substr($r->person_name ?? '?', 0, 1)) }}
                                            </div>
                                            <div class="space-y-0.5">
                                                <div class="flex items-center gap-2">
                                                    <p class="font-bold leading-tight text-slate-900">{{ $r->person_name }}
                                                    </p>
                                                    <span
                                                        class="px-1.5 py-0.2 text-[9px] font-bold uppercase tracking-wider rounded border
                                                        {{ $r->category == 'transportation' ? 'bg-blue-50 text-blue-700 border-blue-200/60' : ($r->category == 'delivery' ? 'bg-purple-50 text-purple-700 border-purple-200/60' : 'bg-slate-100 text-slate-600 border-slate-200') }}">
                                                        {{ $r->category }}
                                                    </span>
                                                </div>
                                                <div class="flex items-center gap-2 text-[11px] text-slate-400">
                                                    <span>{{ \Carbon\Carbon::parse($r->date)->format('d M Y') }}</span>
                                                    @if (in_array($r->category, ['transportation', 'delivery']))
                                                        <span>•</span>
                                                        <span class="text-slate-500 font-normal truncate max-w-[180px]">
                                                            {{ $r->from_location }} <i
                                                                class="fa-solid fa-arrow-right text-[9px] mx-0.5 text-slate-300"></i>
                                                            {{ $r->to_location }}
                                                        </span>
                                                    @elseif($r->comment)
                                                        <span>•</span>
                                                        <span class="font-mono text-slate-500 text-[10px]">Ref:
                                                            {{ $r->comment }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Column 2: Amount --}}
                                    <td class="px-5 py-3.5 text-right font-mono font-bold text-slate-900 text-sm">
                                        Rp {{ number_format((float) ($r->amount ?? 0), 0, ',', '.') }}
                                    </td>

                                    {{-- Column 3: Status --}}
                                    <td class="px-5 py-3.5 text-center">
                                        @if ($r->status == 'approved')
                                            <span
                                                class="inline-flex items-center gap-1 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-emerald-700 bg-emerald-50 border border-emerald-200/80 rounded-md">
                                                <i class="fa-solid fa-check text-[9px]"></i> Approved
                                            </span>
                                        @elseif($r->status == 'rejected')
                                            <span
                                                class="inline-flex items-center gap-1 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-rose-700 bg-rose-50 border border-rose-200/80 rounded-md">
                                                <i class="fa-solid fa-xmark text-[9px]"></i> Rejected
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex items-center gap-1.5 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-amber-700 bg-amber-50 border border-amber-200/80 rounded-md">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                                {{ str_replace('_', ' ', $r->status) }}
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Column 4: Direct Action Buttons --}}
                                    <td class="px-5 py-3.5 text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            {{-- Fast Approve --}}
                                            @if (in_array($r->status, ['pending', 'pending_leader', 'pending_station', 'pending_manager']) &&
                                                    in_array(Auth::user()->role, ['superadmin', 'station_master', 'manager']))
                                                <form action="{{ route('reimbursements.fast_approve', $r->id) }}"
                                                    method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" title="Fast Approve"
                                                        class="inline-flex items-center justify-center transition-colors border rounded-lg cursor-pointer w-7 h-7 text-emerald-600 bg-emerald-50 border-emerald-100 hover:bg-emerald-600 hover:text-white">
                                                        <i class="fa-solid fa-check-double text-[11px]"></i>
                                                    </button>
                                                </form>
                                            @endif

                                            {{-- Quick View Details --}}
                                            <button type="button" onclick="openDetailModal(this)"
                                                data-reimbursement="{{ json_encode($r, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) }}"
                                                title="View Details & Receipt Proof"
                                                class="inline-flex items-center justify-center transition-colors border rounded-lg cursor-pointer w-7 h-7 text-slate-600 bg-slate-100 border-slate-200/80 hover:bg-slate-900 hover:text-white">
                                                <i class="fa-solid fa-eye text-[11px]"></i>
                                            </button>

                                            {{-- Sign Approval --}}
                                            <a href="{{ route('reimbursements.approval', $r->id) }}"
                                                title="Digital Signature"
                                                class="inline-flex items-center justify-center text-blue-600 transition-colors border border-blue-100 rounded-lg w-7 h-7 bg-blue-50 hover:bg-blue-600 hover:text-white">
                                                <i class="fa-solid fa-pen-nib text-[11px]"></i>
                                            </a>

                                            {{-- Download PDF --}}
                                            <a href="{{ route('reimbursements.export_single_pdf', $r->id) }}" download
                                                title="Download PDF Invoice"
                                                class="inline-flex items-center justify-center transition-colors border rounded-lg w-7 h-7 text-rose-600 bg-rose-50 border-rose-100 hover:bg-rose-600 hover:text-white">
                                                <i class="fa-solid fa-file-pdf text-[11px]"></i>
                                            </a>

                                            {{-- Edit --}}
                                            <a href="{{ route('reimbursements.edit', $r->id) }}" title="Edit Claim"
                                                class="inline-flex items-center justify-center transition-colors border rounded-lg w-7 h-7 text-slate-500 bg-slate-50 border-slate-200/80 hover:bg-slate-200 hover:text-slate-800">
                                                <i class="fa-solid fa-pen-to-square text-[11px]"></i>
                                            </a>

                                            {{-- Archive --}}
                                            <button type="button"
                                                onclick="confirmCancel('{{ $r->id }}', '{{ $r->person_name }}', 'Rp {{ number_format((float) ($r->amount ?? 0), 0, ',', '.') }}')"
                                                title="Move to Recycle Bin"
                                                class="inline-flex items-center justify-center transition-colors border rounded-lg cursor-pointer w-7 h-7 text-rose-600 bg-rose-50 border-rose-100 hover:bg-rose-600 hover:text-white">
                                                <i class="fa-solid fa-trash-can text-[11px]"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="p-10 text-center bg-slate-50/30">
                                        <div class="max-w-xs mx-auto text-center">
                                            <div
                                                class="flex items-center justify-center w-10 h-10 mx-auto mb-2 border rounded-xl bg-slate-100 text-slate-400 border-slate-200">
                                                <i class="text-sm fa-solid fa-receipt"></i>
                                            </div>
                                            <p class="text-xs font-bold text-slate-800">No Claims Found</p>
                                            <p class="mt-0.5 text-[11px] text-slate-400">There are no reimbursement records
                                                matching your filter criteria.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- MOBILE VIEW CARDS --}}
                <div id="mobileCardContainer" class="p-4 space-y-3 md:hidden bg-slate-50/50">
                    @forelse ($reimbursements as $r)
                        <div class="p-3.5 bg-white border border-slate-200 rounded-xl space-y-2.5 shadow-2xs">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="w-7 h-7 font-bold text-slate-700 bg-slate-100 border border-slate-200 rounded-lg flex items-center justify-center text-[11px] shrink-0">
                                        {{ strtoupper(substr($r->person_name ?? '?', 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold leading-tight text-slate-900">{{ $r->person_name }}
                                        </p>
                                        <p class="text-[10px] text-slate-400">
                                            {{ \Carbon\Carbon::parse($r->date)->format('d M Y') }}</p>
                                    </div>
                                </div>
                                <span
                                    class="px-2 py-0.5 text-[9px] font-bold rounded border uppercase
                                    {{ $r->status == 'approved' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : ($r->status == 'rejected' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-amber-50 text-amber-700 border-amber-200') }}">
                                    {{ str_replace('_', ' ', $r->status) }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between p-2 text-xs rounded-lg bg-slate-50">
                                <span class="text-[10px] font-bold text-slate-400 uppercase">{{ $r->category }}</span>
                                <span class="font-mono font-bold text-slate-900">Rp
                                    {{ number_format((float) ($r->amount ?? 0), 0, ',', '.') }}</span>
                            </div>

                            <div class="flex items-center justify-between gap-1 pt-1 border-t border-slate-100">
                                <div class="flex gap-1">
                                    <a href="{{ route('reimbursements.edit', $r->id) }}"
                                        class="p-2 text-xs rounded-lg text-slate-600 bg-slate-100">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    <button type="button" onclick="openDetailModal(this)"
                                        data-reimbursement="{{ json_encode($r, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) }}"
                                        class="p-2 text-xs rounded-lg cursor-pointer text-slate-600 bg-slate-100">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                    <button type="button"
                                        onclick="confirmCancel('{{ $r->id }}', '{{ $r->person_name }}', 'Rp {{ number_format((float) ($r->amount ?? 0), 0, ',', '.') }}')"
                                        class="p-2 text-xs rounded-lg cursor-pointer text-rose-600 bg-rose-50">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </div>

                                <div class="flex items-center gap-1.5">
                                    @if (in_array($r->status, ['pending', 'pending_leader', 'pending_station', 'pending_manager']) &&
                                            in_array(Auth::user()->role, ['superadmin', 'station_master', 'manager']))
                                        <form action="{{ route('reimbursements.fast_approve', $r->id) }}" method="POST">
                                            @csrf
                                            <button type="submit"
                                                class="px-2.5 py-1.5 text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-xl cursor-pointer">
                                                <i class="fa-solid fa-check-double"></i>
                                            </button>
                                        </form>
                                    @endif
                                    <a href="{{ route('reimbursements.approval', $r->id) }}"
                                        class="px-3 py-1 text-xs font-bold text-white bg-slate-900 rounded-xl">
                                        Sign
                                    </a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-6 text-xs text-center bg-white border text-slate-400 border-slate-200 rounded-xl">
                            No claims found for this criteria.
                        </div>
                    @endforelse
                </div>

                {{-- PAGINATION --}}
                @if ($reimbursements->hasPages())
                    <div
                        class="flex flex-col gap-3 p-4 border-t sm:flex-row sm:items-center sm:justify-between border-slate-100 bg-slate-50/40 ajax-pagination">
                        <p class="text-xs font-medium text-slate-500">
                            Showing <strong class="text-slate-800">{{ $reimbursements->firstItem() }}</strong> – <strong
                                class="text-slate-800">{{ $reimbursements->lastItem() }}</strong> of <strong
                                class="text-slate-800">{{ $reimbursements->total() }}</strong> claims
                        </p>

                        <div class="flex items-center gap-1">
                            @if ($reimbursements->onFirstPage())
                                <span
                                    class="px-2.5 py-1 text-xs font-semibold text-slate-300 bg-slate-100 rounded-lg cursor-not-allowed">
                                    <i class="fa-solid fa-chevron-left"></i>
                                </span>
                            @else
                                <a href="{{ $reimbursements->previousPageUrl() }}"
                                    class="px-2.5 py-1 text-xs font-semibold text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">
                                    <i class="fa-solid fa-chevron-left"></i>
                                </a>
                            @endif

                            @foreach ($reimbursements->getUrlRange(1, $reimbursements->lastPage()) as $page => $url)
                                @if ($page == $reimbursements->currentPage())
                                    <span class="px-3 py-1 text-xs font-bold text-white rounded-lg bg-slate-900">
                                        {{ $page }}
                                    </span>
                                @else
                                    <a href="{{ $url }}"
                                        class="px-3 py-1 text-xs font-semibold transition-colors bg-white border rounded-lg text-slate-600 border-slate-200 hover:bg-slate-50">
                                        {{ $page }}
                                    </a>
                                @endif
                            @endforeach

                            @if ($reimbursements->hasMorePages())
                                <a href="{{ $reimbursements->nextPageUrl() }}"
                                    class="px-2.5 py-1 text-xs font-semibold text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">
                                    <i class="fa-solid fa-chevron-right"></i>
                                </a>
                            @else
                                <span
                                    class="px-2.5 py-1 text-xs font-semibold text-slate-300 bg-slate-100 rounded-lg cursor-not-allowed">
                                    <i class="fa-solid fa-chevron-right"></i>
                                </span>
                            @endif
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </div>

    {{-- MODAL 1: PREVIEW DETAILS --}}
    <div id="detailModal" onclick="if(event.target===this) closeDetailModal()"
        class="fixed inset-0 z-50 flex items-center justify-center hidden p-4 transition-opacity bg-slate-900/40 backdrop-blur-xs">
        <div
            class="relative w-full max-w-3xl bg-white border border-slate-200 shadow-2xl rounded-2xl flex flex-col max-h-[90vh] overflow-hidden">

            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/60 shrink-0">
                <div class="flex items-center gap-2">
                    <span id="modal-category"
                        class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-md bg-slate-200 text-slate-800">
                        Category
                    </span>
                    <h3 class="text-sm font-bold text-slate-900">Claim Details & Receipt Proof</h3>
                </div>
                <button onclick="closeDetailModal()" type="button"
                    class="cursor-pointer text-slate-400 hover:text-slate-600">
                    <i class="text-lg fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="grid grid-cols-1 gap-6 p-6 overflow-y-auto text-xs lg:grid-cols-5">
                <div class="space-y-4 lg:col-span-2">
                    <div class="p-3.5 border border-slate-200/80 bg-slate-50/50 rounded-xl space-y-2">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Requester
                                Name</span>
                            <p id="modal-name" class="font-bold text-slate-900 text-sm mt-0.5">-</p>
                        </div>
                        <div class="pt-2 border-t border-slate-200/60">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Date
                                Filed</span>
                            <p id="modal-date" class="font-medium text-slate-800 mt-0.5">-</p>
                        </div>
                    </div>

                    <div class="p-3.5 border border-slate-200/80 rounded-xl space-y-2">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Claim
                                Amount</span>
                            <p id="modal-amount" class="text-lg font-mono font-bold text-slate-900 mt-0.5">Rp 0</p>
                        </div>
                        <div class="pt-2 border-t border-slate-100">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Route
                                Info</span>
                            <p id="modal-route" class="font-medium text-slate-800 mt-0.5">-</p>
                        </div>
                    </div>

                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1">Notes /
                            Description</span>
                        <div id="modal-comment"
                            class="p-3 italic border border-slate-200 bg-slate-50/50 rounded-xl text-slate-700">
                            "No description provided."
                        </div>
                    </div>

                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1.5">Approval
                            Hierarchy Trail</span>
                        <div class="p-3 space-y-2 border border-slate-200/80 rounded-xl bg-slate-50/30">
                            <div class="flex items-center justify-between pb-1.5 border-b border-slate-200/60">
                                <span class="font-semibold text-slate-700">1. Staff Requester</span>
                                <span id="sign-status-staff" class="px-2 py-0.5 rounded text-[10px] font-bold"></span>
                            </div>
                            <div class="flex items-center justify-between pb-1.5 border-b border-slate-200/60">
                                <span class="font-semibold text-slate-700">2. Team Leader</span>
                                <span id="sign-status-leader" class="px-2 py-0.5 rounded text-[10px] font-bold"></span>
                            </div>
                            <div class="flex items-center justify-between pb-1.5 border-b border-slate-200/60">
                                <span class="font-semibold text-slate-700">3. Station Master</span>
                                <span id="sign-status-station" class="px-2 py-0.5 rounded text-[10px] font-bold"></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="font-semibold text-slate-700">4. Operational Manager</span>
                                <span id="sign-status-manager" class="px-2 py-0.5 rounded text-[10px] font-bold"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-3 flex flex-col space-y-1.5 min-h-[300px]">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Receipt
                        Attachment</span>
                    <div id="modal-attachment-frame"
                        class="relative flex-1 w-full overflow-hidden border border-slate-200 bg-slate-100 rounded-xl">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL 2: CANCEL / DELETE CONFIRMATION --}}
    <div id="cancelModal" onclick="if(event.target===this) closeCancelModal()"
        class="fixed inset-0 z-50 flex items-center justify-center hidden p-4 transition-opacity bg-slate-900/40 backdrop-blur-xs">
        <div
            class="relative w-full max-w-sm p-6 space-y-4 text-center bg-white border shadow-2xl border-slate-200 rounded-2xl">
            <div
                class="flex items-center justify-center w-12 h-12 mx-auto border rounded-full bg-slate-100 text-slate-700 border-slate-200">
                <i class="text-lg fa-solid fa-box-archive"></i>
            </div>
            <div>
                <h3 class="text-sm font-bold text-slate-900">Archive This Claim?</h3>
                <p class="mt-1 text-xs leading-relaxed text-slate-500">
                    Claim filed by <strong id="cancel_person_name" class="text-slate-800"></strong> (<span
                        id="cancel_amount" class="font-mono font-bold text-slate-900"></span>) will be moved to the
                    <strong>Recycle Bin / Archive</strong> folder.
                </p>
            </div>
            <form method="POST" action="" class="flex gap-2 pt-2">
                @csrf @method('DELETE')
                <button type="button" onclick="closeCancelModal()"
                    class="flex-1 py-2 text-xs font-semibold transition-colors cursor-pointer bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl">
                    Cancel
                </button>
                <button type="submit"
                    class="flex-1 py-2 text-xs font-semibold text-white transition-colors cursor-pointer bg-slate-900 hover:bg-slate-800 rounded-xl shadow-2xs">
                    Archive
                </button>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        let debounceTimer;

        function fetchReimbursementData(targetUrl = null) {
            const wrapper = document.getElementById('reimbursementDataWrapper');
            const searchInput = document.getElementById('reimburseSearchInput');
            const monthSelect = document.getElementById('reimburseMonthSelect');

            const searchValue = searchInput ? searchInput.value.trim() : '';
            const monthValue = monthSelect ? monthSelect.value : '';

            if (wrapper) wrapper.style.opacity = '0.5';

            let url = targetUrl ? new URL(targetUrl, window.location.origin) : new URL(
                "{{ route('reimbursements.index') }}", window.location.origin);

            if (searchValue) url.searchParams.set('search', searchValue);
            else url.searchParams.delete('search');

            if (monthValue) url.searchParams.set('month', monthValue);
            else url.searchParams.delete('month');

            const clearBtn = document.getElementById('clearSearchBtn');
            if (clearBtn) {
                clearBtn.classList.toggle('hidden', !searchValue);
            }

            const pdfLink = document.getElementById('pdfExportLink');
            if (pdfLink) {
                const pdfUrl = new URL("{{ route('reimbursements.export_pdf') }}", window.location.origin);
                if (monthValue) pdfUrl.searchParams.set('month', monthValue);
                pdfLink.href = pdfUrl.href;
            }

            fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');

                    const newContent = doc.getElementById('reimbursementDataWrapper');
                    if (newContent && wrapper) {
                        wrapper.innerHTML = newContent.innerHTML;
                    }

                    const newApprovedText = doc.getElementById('totalApprovedAmountText');
                    const currentApprovedText = document.getElementById('totalApprovedAmountText');
                    if (newApprovedText && currentApprovedText) {
                        currentApprovedText.innerHTML = newApprovedText.innerHTML;
                    }

                    if (wrapper) wrapper.style.opacity = '1';

                    window.history.pushState({}, '', url);
                    bindPaginationEvents();
                })
                .catch(error => {
                    console.error('AJAX Fetch Error:', error);
                    if (wrapper) wrapper.style.opacity = '1';
                });
        }

        function initReimbursementFilters() {
            const searchInput = document.getElementById('reimburseSearchInput');
            const monthSelect = document.getElementById('reimburseMonthSelect');

            if (searchInput) {
                searchInput.removeEventListener('input', handleSearchInput);
                searchInput.addEventListener('input', handleSearchInput);
            }

            if (monthSelect) {
                monthSelect.removeEventListener('change', handleMonthChange);
                monthSelect.addEventListener('change', handleMonthChange);
            }

            bindPaginationEvents();
        }

        function handleSearchInput() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                fetchReimbursementData();
            }, 350);
        }

        function handleMonthChange() {
            fetchReimbursementData();
        }

        function clearSearch() {
            const input = document.getElementById('reimburseSearchInput');
            if (input) {
                input.value = '';
                fetchReimbursementData();
            }
        }

        function bindPaginationEvents() {
            const paginationLinks = document.querySelectorAll('.ajax-pagination a');
            paginationLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    fetchReimbursementData(this.href);
                });
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            initReimbursementFilters();

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeDetailModal();
                    closeCancelModal();
                }
            });
        });

        function exportExcelReport() {
            const url = new URL('{{ route('reimbursements.export_excel') }}', window.location.origin);
            const urlParams = new URLSearchParams(window.location.search);
            const currentMonth = urlParams.get('month');
            const currentSearch = urlParams.get('search');

            if (currentMonth) url.searchParams.set('month', currentMonth);
            if (currentSearch) url.searchParams.set('search', currentSearch);

            // @if (Auth::user()?->role === 'superadmin')
            //     url.searchParams.set('all_site', '1');
            // @endif

            @if (in_array(Auth::user()?->role, ['superadmin', 'administration']))
                url.searchParams.set('all_site', '1');
            @endif

            window.location.href = url.href;
        }

        function openDetailModal(buttonElement) {
            const rawData = buttonElement.getAttribute('data-reimbursement');
            let data;
            try {
                data = typeof rawData === 'string' ? JSON.parse(rawData) : rawData;
            } catch (e) {
                console.error("Failed to parse reimbursement json:", e);
                alert("Failed to load details. Please try again.");
                return;
            }

            document.getElementById('modal-name').innerText = data.person_name || '-';
            document.getElementById('modal-category').innerText = data.category || 'Claim';
            document.getElementById('modal-comment').innerText = data.comment ? `"${data.comment}"` :
                "No description provided.";

            const dateObj = new Date(data.date);
            document.getElementById('modal-date').innerText = isNaN(dateObj) ? '-' : dateObj.toLocaleDateString('en-US', {
                day: '2-digit',
                month: 'short',
                year: 'numeric'
            });

            document.getElementById('modal-amount').innerText = new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(data.amount || 0);

            if (data.category === 'transportation' || data.category === 'delivery') {
                document.getElementById('modal-route').innerHTML =
                    `<span class="font-semibold text-slate-900">${data.from_location || '-'}</span> <i class="mx-1 fa-solid fa-arrow-right text-slate-300"></i> <span class="font-semibold text-slate-900">${data.to_location || '-'}</span>`;
            } else {
                document.getElementById('modal-route').innerText = "Routing Exempted";
            }

            let signatures = [];
            if (data.signatures_json) {
                try {
                    signatures = typeof data.signatures_json === 'string' ? JSON.parse(data.signatures_json) : data
                        .signatures_json;
                    if (!Array.isArray(signatures)) signatures = [];
                } catch (e) {
                    signatures = [];
                }
            }

            function renderSignBadge(elementId, isSigned, fallbackText = "Unsigned") {
                const el = document.getElementById(elementId);
                if (!el) return;
                if (isSigned) {
                    el.innerText = "✓ Signed";
                    el.className =
                        "px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200";
                } else {
                    el.innerText = fallbackText;
                    el.className =
                        "px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-400 border border-slate-200";
                }
            }

            const hasStaff = signatures.some(s => s && (s.role === 'employee_role' || s.level === 'employee_role')) || !!
                data.person_name;
            const hasLeader = signatures.some(s => s && (s.role === 'leader' || s.level === 'leader')) || (data.status !==
                'pending' && data.status !== 'pending_leader');
            const hasStation = signatures.some(s => s && (s.role === 'station_master' || s.role === 'station')) || (data
                .status === 'approved' || data.status === 'pending_manager');
            const hasManager = signatures.some(s => s && s.role === 'manager') || data.status === 'approved';

            renderSignBadge('sign-status-staff', hasStaff, "Pending");
            renderSignBadge('sign-status-leader', hasLeader, "Pending Review");
            renderSignBadge('sign-status-station', hasStation, "Pending Approval");
            renderSignBadge('sign-status-manager', hasManager, "Pending Disbursement");

            if (data.status === 'rejected') {
                if (!hasLeader) renderSignBadge('sign-status-leader', false, "✖ Rejected");
                else if (!hasStation) renderSignBadge('sign-status-station', false, "✖ Rejected");
                else if (!hasManager) renderSignBadge('sign-status-manager', false, "✖ Rejected");
            }

            const frame = document.getElementById('modal-attachment-frame');
            frame.innerHTML = '';

            if (data.receipt_attachment) {
                const fileExt = data.receipt_attachment.split('.').pop().toLowerCase();
                const fullUrl = `/storage/${data.receipt_attachment}`;

                if (fileExt === 'pdf') {
                    frame.innerHTML =
                        `<object data="${fullUrl}#toolbar=0" type="application/pdf" class="block w-full h-full min-h-[280px]"></object>`;
                } else {
                    frame.innerHTML =
                        `<div class="flex items-center justify-center w-full h-full p-2 bg-slate-50"><img src="${fullUrl}" class="object-contain max-w-full max-h-full rounded-lg" /></div>`;
                }
            } else {
                frame.innerHTML =
                    `<div class="flex items-center justify-center w-full h-full p-8 text-xs italic text-slate-400">No receipt document proof attached.</div>`;
            }

            const m = document.getElementById('detailModal');
            if (m) {
                m.classList.remove('hidden');
                m.classList.add('flex');
            }
            document.body.classList.add('overflow-hidden');
        }

        function closeDetailModal() {
            const m = document.getElementById('detailModal');
            if (m) {
                m.classList.add('hidden');
                m.classList.remove('flex');
            }
            document.body.classList.remove('overflow-hidden');
        }

        function confirmCancel(id, personName = '', amount = '') {
            const modal = document.getElementById('cancelModal');
            if (!modal) return;

            const form = modal.querySelector('form');
            if (form) form.action = `/reimbursements/${id}`;

            const nameEl = document.getElementById('cancel_person_name');
            const amountEl = document.getElementById('cancel_amount');
            if (nameEl) nameEl.innerText = personName;
            if (amountEl) amountEl.innerText = amount;

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        }

        function closeCancelModal() {
            const modal = document.getElementById('cancelModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
            document.body.classList.remove('overflow-hidden');
        }
    </script>
@endpush
