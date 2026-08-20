@extends('layout.master')

@section('title', 'Reimbursement Claims')

@section('content')
    <div class="w-full space-y-6">

        {{-- 1. HEADER CARD (TERPISAH) --}}
        <div class="p-6 bg-white border shadow-xs sm:p-8 border-slate-200/80 rounded-3xl">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div
                        class="inline-flex items-center gap-2 px-3 py-1 mb-2 text-xs font-bold border rounded-full bg-amber-50 border-amber-100 text-amber-800">
                        <i class="fa-solid fa-receipt text-[10px]"></i> Expense Tracking
                    </div>
                    <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl text-slate-900">
                        {{ $pageTitle ?? 'Reimbursement Claims' }}
                    </h1>
                    <p class="mt-1 text-xs font-semibold sm:text-sm text-slate-500">
                        Track, audit, and manage operational expense claims across all site units.
                    </p>
                </div>

                {{-- Quick Stats Approved Funds --}}
                <div
                    class="flex items-center gap-3.5 px-5 py-3 bg-slate-50/80 border border-slate-200/80 rounded-2xl shrink-0">
                    <div class="p-2.5 bg-emerald-50 text-emerald-600 rounded-xl border border-emerald-100">
                        <i class="text-lg fa-solid fa-money-bill-wave"></i>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold uppercase text-slate-400 tracking-wider block">Total Approved
                            Funds</span>
                        <span class="text-lg font-black sm:text-xl text-slate-900">
                            Rp {{ number_format($totalApprovedAmount ?? 0, 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. MAIN TABLE & TOOLBAR CARD --}}
        <div class="overflow-hidden bg-white border shadow-xs border-slate-200/80 rounded-3xl">

            {{-- TOOLBAR & FILTER SECTION --}}
            <div
                class="flex flex-col justify-between gap-4 p-5 border-b sm:p-6 lg:flex-row lg:items-center border-slate-100 bg-slate-50/30">

                {{-- SEARCH & MONTH FILTER --}}
                <div class="flex flex-col flex-1 gap-3 sm:flex-row sm:items-center">
                    <div class="relative w-full sm:w-64">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                            <i class="text-xs fa-solid fa-magnifying-glass" id="searchIcon"></i>
                        </span>
                        <input type="text" id="reimburseSearchInput" value="{{ request('search') }}"
                            placeholder="Search staff, route, invoice..."
                            class="w-full py-2.5 pl-10 pr-8 text-xs font-medium bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 text-slate-800 transition-all shadow-2xs">

                        <button type="button" id="clearSearchBtn" onclick="clearSearch()"
                            class="{{ request('search') ? '' : 'hidden' }} absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600">
                            <i class="text-xs fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    {{-- MONTH FILTER --}}
                    <div class="relative w-full sm:w-auto">
                        <select id="reimburseMonthSelect"
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
                </div>

                {{-- ACTION BUTTONS --}}
                <div class="flex flex-wrap items-center gap-2.5">
                    <a href="{{ route('reimbursements.create') }}"
                        class="flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-bold text-white transition-all bg-amber-600 hover:bg-amber-700 rounded-xl shadow-md shadow-amber-600/20 active:scale-95">
                        <i class="text-xs fa-solid fa-plus"></i>
                        <span>File New Claim</span>
                    </a>
                    <a href="{{ route('reimbursements.export_pdf', ['month' => request('month')]) }}" id="pdfExportLink"
                        class="flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-bold text-white transition-all bg-blue-600 hover:bg-blue-700 rounded-xl shadow-md shadow-blue-600/20 active:scale-95">
                        <i class="fas fa-file-pdf"></i> PDF Summary
                    </a>
                    <a href="javascript:void(0)" onclick="exportExcelReport()"
                        class="flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-bold text-white transition-all bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-md shadow-emerald-600/20 active:scale-95">
                        <i class="fa-solid fa-file-excel"></i> Export Excel
                    </a>
                </div>
            </div>

            {{-- DYNAMIC DATA CONTAINER (AJAX UPDATED) --}}
            <div id="reimbursementDataWrapper" class="transition-opacity duration-200">

                {{-- DESKTOP VIEW TABLE --}}
                <div id="desktopTableContainer" class="hidden overflow-x-auto md:block">
                    <table class="w-full text-left border-collapse min-w-[750px]">
                        <thead>
                            <tr
                                class="text-[10px] font-extrabold uppercase tracking-wider text-slate-500 bg-slate-50 border-b border-slate-100">
                                <th class="w-16 px-6 py-4 text-center">No</th>
                                <th class="px-6 py-4">Requester / Date</th>
                                <th class="px-6 py-4">Category</th>
                                <th class="px-6 py-4">Details / Route</th>
                                <th class="px-6 py-4 text-center">Amount</th>
                                <th class="px-6 py-4 text-center">No. Invoice</th>
                                <th class="px-6 py-4 text-center">Status</th>
                                <th class="w-48 px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="text-xs font-medium divide-y divide-slate-100 text-slate-700">
                            @forelse ($reimbursements as $r)
                                <tr class="transition-colors hover:bg-slate-50/60">
                                    <td class="px-6 py-4 font-bold text-center text-slate-400">
                                        {{ ($reimbursements->currentPage() - 1) * $reimbursements->perPage() + $loop->iteration }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="flex items-center justify-center w-8 h-8 text-xs font-black border text-amber-700 bg-amber-50 rounded-xl shrink-0 border-amber-100">
                                                {{ strtoupper(substr($r->person_name ?? '?', 0, 1)) }}
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold leading-snug text-slate-900">
                                                    {{ $r->person_name }}</p>
                                                <p class="text-[11px] font-medium text-slate-400 mt-0.5">
                                                    {{ \Carbon\Carbon::parse($r->date)->format('d M Y') }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span
                                            class="px-2.5 py-1 text-[10px] font-extrabold rounded-lg uppercase tracking-wider
                                            {{ $r->category == 'transportation' ? 'bg-blue-50 text-blue-700 border border-blue-200/60' : ($r->category == 'delivery' ? 'bg-purple-50 text-purple-700 border border-purple-200/60' : 'bg-slate-100 text-slate-700 border border-slate-200') }}">
                                            {{ $r->category }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        @if (in_array($r->category, ['transportation', 'delivery']))
                                            <p class="text-xs font-semibold text-slate-700">
                                                <i class="mr-1 fa-solid fa-location-dot text-rose-500"></i>
                                                {{ $r->from_location }}
                                                <i class="mx-1 fa-solid fa-arrow-right text-slate-400 text-[10px]"></i>
                                                {{ $r->to_location }}
                                            </p>
                                        @else
                                            <p class="text-xs italic text-slate-400">No routing required</p>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="text-xs font-black text-slate-900">
                                            Rp {{ number_format($r->amount, 0, ',', '.') }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="font-mono text-xs font-bold text-slate-600">
                                            {{ $r->comment ?: '-' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if ($r->status == 'approved')
                                            <span
                                                class="px-2.5 py-1 text-[10px] font-extrabold text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-lg uppercase">Approved</span>
                                        @elseif($r->status == 'rejected')
                                            <span
                                                class="px-2.5 py-1 text-[10px] font-extrabold text-rose-800 bg-rose-50 border border-rose-200 rounded-lg uppercase">Rejected</span>
                                        @else
                                            <span
                                                class="px-2.5 py-1 text-[10px] font-extrabold text-amber-800 bg-amber-50 border border-amber-200 rounded-lg uppercase animate-pulse">
                                                {{ strtoupper(str_replace('_', ' ', $r->status)) }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            {{-- EDIT --}}
                                            <a href="{{ route('reimbursements.edit', $r->id) }}"
                                                class="flex items-center justify-center w-8 h-8 transition-all border rounded-xl text-amber-600 bg-amber-50 border-amber-100 hover:bg-amber-600 hover:text-white active:scale-95"
                                                title="Edit Claim">
                                                <i class="text-xs fa-solid fa-pen-to-square"></i>
                                            </a>

                                            {{-- SINGLE PDF --}}
                                            <a href="{{ route('reimbursements.export_single_pdf', $r->id) }}"
                                                class="flex items-center justify-center w-8 h-8 transition-all border rounded-xl text-rose-600 bg-rose-50 border-rose-100 hover:bg-rose-600 hover:text-white active:scale-95"
                                                title="Download Invoice PDF">
                                                <i class="text-xs fas fa-file-pdf"></i>
                                            </a>

                                            {{-- QUICK VIEW --}}
                                            <button onclick="openDetailModal(this)"
                                                data-reimbursement="{{ json_encode($r) }}"
                                                class="flex items-center justify-center w-8 h-8 transition-all border rounded-xl text-slate-600 bg-slate-100 border-slate-200 hover:bg-slate-900 hover:text-white active:scale-95"
                                                title="Quick View Details">
                                                <i class="text-xs fa-solid fa-receipt"></i>
                                            </button>

                                            {{-- SIGN APPROVAL --}}
                                            <a href="{{ route('reimbursements.approval', $r->id) }}"
                                                class="flex items-center justify-center w-8 h-8 text-blue-600 transition-all border border-blue-100 rounded-xl bg-blue-50 hover:bg-blue-600 hover:text-white active:scale-95"
                                                title="Digital Signature Page">
                                                <i class="text-xs fa-solid fa-pen-nib"></i>
                                            </a>

                                            {{-- CANCEL / DELETE --}}
                                            <button onclick="confirmCancel('{{ $r->id }}')"
                                                class="flex items-center justify-center w-8 h-8 transition-all border rounded-xl text-rose-600 bg-rose-50 border-rose-100 hover:bg-rose-600 hover:text-white active:scale-95"
                                                title="Cancel Claim">
                                                <i class="text-xs fa-solid fa-ban"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="p-12 text-center text-slate-400">
                                        <div
                                            class="flex items-center justify-center w-12 h-12 mx-auto mb-3 text-xl rounded-2xl bg-slate-100 text-slate-400">
                                            <i class="fa-solid fa-receipt"></i>
                                        </div>
                                        <p class="text-sm font-bold text-slate-800">No Claims Found</p>
                                        <p class="mt-1 text-xs text-slate-400">We couldn't find any reimbursement claims
                                            matching your filter criteria.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- MOBILE VIEW CARDS --}}
                <div id="mobileCardContainer" class="p-4 space-y-3 md:hidden bg-slate-50/50">
                    @forelse ($reimbursements as $r)
                        <div class="p-4 space-y-3 bg-white border border-slate-200/80 shadow-2xs rounded-2xl">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-center gap-2.5">
                                    <div
                                        class="flex items-center justify-center w-8 h-8 text-xs font-black border rounded-xl text-amber-700 bg-amber-50 shrink-0 border-amber-100">
                                        {{ strtoupper(substr($r->person_name ?? '?', 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-slate-900">{{ $r->person_name }}</p>
                                        <p class="text-[10px] text-slate-400">
                                            {{ \Carbon\Carbon::parse($r->date)->format('d M Y') }}
                                        </p>
                                    </div>
                                </div>
                                <div>
                                    @if ($r->status == 'approved')
                                        <span
                                            class="px-2 py-0.5 text-[9px] font-bold text-emerald-800 bg-emerald-50 rounded-md uppercase border border-emerald-200">Approved</span>
                                    @elseif($r->status == 'rejected')
                                        <span
                                            class="px-2 py-0.5 text-[9px] font-bold text-rose-800 bg-rose-50 rounded-md uppercase border border-rose-200">Rejected</span>
                                    @else
                                        <span
                                            class="px-2 py-0.5 text-[9px] font-bold text-amber-800 bg-amber-50 rounded-md uppercase border border-amber-200 animate-pulse">Pending</span>
                                    @endif
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-2 p-3 text-xs border bg-slate-50 rounded-xl border-slate-100">
                                <div>
                                    <span class="block text-[10px] font-bold text-slate-400 uppercase">Category</span>
                                    <span
                                        class="font-bold text-slate-800 uppercase text-[11px]">{{ $r->category }}</span>
                                </div>
                                <div>
                                    <span class="block text-[10px] font-bold text-slate-400 uppercase">Amount</span>
                                    <span class="font-black text-slate-900 text-[11px]">Rp
                                        {{ number_format($r->amount, 0, ',', '.') }}</span>
                                </div>
                            </div>

                            <div class="flex gap-2 pt-1">
                                <a href="{{ route('reimbursements.edit', $r->id) }}"
                                    class="p-2 text-amber-700 bg-amber-50 border border-amber-100 hover:bg-amber-100 rounded-xl font-bold text-xs flex-1 text-center flex justify-center items-center gap-1.5 transition-colors">
                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                </a>

                                <button onclick="openDetailModal(this)" data-reimbursement="{{ json_encode($r) }}"
                                    class="p-2 text-slate-700 bg-slate-100 border border-slate-200 hover:bg-slate-200 rounded-xl font-bold text-xs flex-1 text-center flex justify-center items-center gap-1.5 transition-colors">
                                    <i class="fa-solid fa-receipt"></i> Details
                                </button>

                                <a href="{{ route('reimbursements.approval', $r->id) }}"
                                    class="p-2 text-white bg-amber-600 hover:bg-amber-700 rounded-xl font-bold text-xs flex-1 text-center flex justify-center items-center gap-1.5 shadow-md shadow-amber-600/20 active:scale-95 transition-all">
                                    <i class="fa-solid fa-pen-nib"></i> Sign Claim
                                </a>
                            </div>
                        </div>
                    @empty
                        <div
                            class="p-8 text-xs font-medium text-center bg-white border border-slate-200 text-slate-400 rounded-2xl">
                            No reimbursement claims filed for this criteria.
                        </div>
                    @endforelse
                </div>

                {{-- PAGINATION LINKS --}}
                @if ($reimbursements->hasPages())
                    <div
                        class="flex flex-col items-center justify-between gap-3 p-4 border-t sm:flex-row sm:p-6 bg-slate-50/50 border-slate-100 ajax-pagination">
                        <p class="text-xs font-medium text-slate-500">
                            Showing <span class="font-bold text-slate-800">{{ $reimbursements->firstItem() }}</span>
                            to <span class="font-bold text-slate-800">{{ $reimbursements->lastItem() }}</span>
                            of <span class="font-bold text-slate-800">{{ $reimbursements->total() }}</span> results
                        </p>

                        <div class="flex items-center gap-1">
                            @if ($reimbursements->onFirstPage())
                                <span
                                    class="px-3 py-1.5 text-xs font-bold text-slate-300 bg-slate-100 rounded-xl cursor-not-allowed">
                                    <i class="fa-solid fa-chevron-left"></i>
                                </span>
                            @else
                                <a href="{{ $reimbursements->previousPageUrl() }}"
                                    class="px-3 py-1.5 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-amber-600 hover:text-white rounded-xl transition-colors">
                                    <i class="fa-solid fa-chevron-left"></i>
                                </a>
                            @endif

                            @foreach ($reimbursements->getUrlRange(1, $reimbursements->lastPage()) as $page => $url)
                                @if ($page == $reimbursements->currentPage())
                                    <span
                                        class="px-3 py-1.5 text-xs font-black text-white bg-amber-600 rounded-xl shadow-xs">
                                        {{ $page }}
                                    </span>
                                @else
                                    <a href="{{ $url }}"
                                        class="px-3 py-1.5 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-amber-600 hover:text-white rounded-xl transition-colors">
                                        {{ $page }}
                                    </a>
                                @endif
                            @endforeach

                            @if ($reimbursements->hasMorePages())
                                <a href="{{ $reimbursements->nextPageUrl() }}"
                                    class="px-3 py-1.5 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-amber-600 hover:text-white rounded-xl transition-colors">
                                    <i class="fa-solid fa-chevron-right"></i>
                                </a>
                            @else
                                <span
                                    class="px-3 py-1.5 text-xs font-bold text-slate-300 bg-slate-100 rounded-xl cursor-not-allowed">
                                    <i class="fa-solid fa-chevron-right"></i>
                                </span>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

        </div>
    </div>

    {{-- MODAL 1: QUICK DETAIL PREVIEW --}}
    <div id="detailModal"
        class="fixed inset-0 z-50 flex items-center justify-center hidden p-4 transition-all duration-300 bg-slate-900/60 backdrop-blur-xs">
        <div
            class="relative w-full max-w-4xl bg-white border border-slate-100 shadow-2xl rounded-3xl flex flex-col max-h-[90vh] overflow-hidden">
            <div class="flex items-center justify-between p-6 border-b border-slate-100 bg-slate-50/50 shrink-0">
                <div class="space-y-0.5">
                    <span id="modal-category"
                        class="px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wider rounded-lg bg-amber-50 text-amber-800 border border-amber-200">Category</span>
                    <h3 class="text-base font-extrabold text-slate-900">Operational Claim Specification</h3>
                </div>
                <button onclick="closeDetailModal()" type="button"
                    class="flex items-center justify-center w-8 h-8 transition-colors rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                    <i class="text-base fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="grid grid-cols-1 gap-6 p-6 overflow-y-auto text-xs lg:grid-cols-5">
                <div class="space-y-4 lg:col-span-2">
                    <div class="grid grid-cols-2 gap-3 p-4 border border-slate-200/80 bg-slate-50/50 rounded-2xl">
                        <div>
                            <span
                                class="text-slate-400 font-bold block uppercase text-[10px] tracking-wider">Requester</span>
                            <p id="modal-name" class="font-bold text-slate-900 mt-0.5 text-sm">-</p>
                        </div>
                        <div>
                            <span class="text-slate-400 font-bold block uppercase text-[10px] tracking-wider">Date
                                Filed</span>
                            <p id="modal-date" class="font-bold text-slate-900 mt-0.5 text-sm">-</p>
                        </div>
                    </div>

                    <div class="p-4 space-y-3 border border-slate-200/80 rounded-2xl">
                        <div>
                            <span class="text-slate-400 font-bold block uppercase text-[10px] tracking-wider">Total Claim
                                Value</span>
                            <p id="modal-amount" class="text-xl font-black text-rose-600 mt-0.5">Rp 0</p>
                        </div>
                        <div class="pt-3 border-t border-slate-100">
                            <span class="text-slate-400 font-bold block uppercase text-[10px] tracking-wider">Route
                                Info</span>
                            <p id="modal-route" class="font-semibold leading-normal text-slate-800 mt-0.5">-</p>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <span class="text-slate-400 font-bold block uppercase text-[10px] tracking-wider">Statement
                            Description</span>
                        <div class="p-3.5 text-xs italic font-medium leading-relaxed border bg-amber-50/40 border-amber-200/60 rounded-2xl text-slate-700"
                            id="modal-comment">
                            "No description provided."
                        </div>
                    </div>

                    <div class="space-y-2">
                        <span
                            class="text-slate-400 font-bold block uppercase text-[10px] tracking-wider flex items-center gap-1.5">
                            <i class="fa-solid fa-timeline text-amber-600"></i> Sign & Approval Status
                        </span>
                        <div class="p-3.5 space-y-2.5 text-xs border bg-slate-50/50 rounded-2xl border-slate-200/80">
                            <div class="flex items-center justify-between pb-2 border-b border-slate-200/60">
                                <span class="flex items-center gap-2 font-bold text-slate-700">
                                    <i class="fa-solid fa-user text-[10px] text-slate-400"></i> 1. Staff Requester
                                </span>
                                <span id="sign-status-staff" class="px-2 py-0.5 rounded-md text-[10px] font-bold"></span>
                            </div>
                            <div class="flex items-center justify-between pb-2 border-b border-slate-200/60">
                                <span class="flex items-center gap-2 font-bold text-slate-700">
                                    <i class="fa-solid fa-user-tie text-[10px] text-slate-400"></i> 2. Team Leader
                                </span>
                                <span id="sign-status-leader" class="px-2 py-0.5 rounded-md text-[10px] font-bold"></span>
                            </div>
                            <div class="flex items-center justify-between pb-2 border-b border-slate-200/60">
                                <span class="flex items-center gap-2 font-bold text-slate-700">
                                    <i class="fa-solid fa-house-laptop text-[10px] text-slate-400"></i> 3. Station Master
                                </span>
                                <span id="sign-status-station"
                                    class="px-2 py-0.5 rounded-md text-[10px] font-bold"></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="flex items-center gap-2 font-bold text-slate-700">
                                    <i class="fa-solid fa-user-gear text-[10px] text-slate-400"></i> 4. Operational Manager
                                </span>
                                <span id="sign-status-manager"
                                    class="px-2 py-0.5 rounded-md text-[10px] font-bold"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-3 flex flex-col space-y-2 min-h-[320px]">
                    <span
                        class="text-slate-400 font-bold block uppercase text-[10px] tracking-wider flex items-center gap-1.5">
                        <i class="fa-solid fa-scroll text-slate-400"></i> Receipt Attachment Preview
                    </span>
                    <div id="modal-attachment-frame"
                        class="relative flex-1 w-full overflow-hidden border bg-slate-100 rounded-2xl border-slate-200/80">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL 2: CANCEL FORM MODAL --}}
    <div id="cancelModal"
        class="fixed inset-0 z-50 flex items-center justify-center hidden p-4 transition-all duration-300 bg-slate-900/60 backdrop-blur-xs">
        <div class="relative w-full max-w-sm p-6 text-center bg-white border shadow-2xl border-slate-100 rounded-3xl">
            <div
                class="flex items-center justify-center w-12 h-12 mx-auto mb-3 border rounded-2xl bg-rose-50 border-rose-100 text-rose-600">
                <i class="text-lg fa-solid fa-triangle-exclamation"></i>
            </div>
            <h3 class="text-base font-extrabold text-slate-900">Cancel Reimbursement Claim?</h3>
            <p class="mt-1 text-xs font-medium text-slate-500">This will permanently delete this operational expense file
                record from the database.</p>
            <form method="POST" action="" class="flex gap-3 mt-6">
                @csrf @method('DELETE')
                <button type="submit"
                    class="flex-1 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs uppercase tracking-wider rounded-xl transition-all active:scale-[0.98] shadow-md shadow-rose-600/20">
                    Yes, Delete
                </button>
                <button type="button" onclick="document.getElementById('cancelModal').classList.add('hidden')"
                    class="flex-1 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs uppercase tracking-wider rounded-xl transition-colors">
                    Dismiss
                </button>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        let debounceTimer;

        function fetchReimbursementData(targetUrl = null) {
            const searchIcon = document.getElementById('searchIcon');
            const wrapper = document.getElementById('reimbursementDataWrapper');
            const searchValue = document.getElementById('reimburseSearchInput').value.trim();
            const monthValue = document.getElementById('reimburseMonthSelect').value;

            if (wrapper) wrapper.style.opacity = '0.5';

            let url = targetUrl ? new URL(targetUrl) : new URL("{{ route('reimbursements.index') }}");
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
                const pdfUrl = new URL("{{ route('reimbursements.export_pdf') }}");
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

                    if (wrapper) wrapper.style.opacity = '1';

                    window.history.pushState({}, '', url);
                    bindPaginationEvents();
                })
                .catch(error => {
                    console.error('AJAX Error:', error);
                    if (wrapper) wrapper.style.opacity = '1';
                });
        }

        document.getElementById('reimburseSearchInput').addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                fetchReimbursementData();
            }, 400);
        });

        document.getElementById('reimburseMonthSelect').addEventListener('change', function() {
            fetchReimbursementData();
        });

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
            bindPaginationEvents();
        });

        function exportExcelReport() {
            const url = new URL('{{ route('reimbursements.export_excel') }}');
            const urlParams = new URLSearchParams(window.location.search);
            const currentMonth = urlParams.get('month');
            const currentSearch = urlParams.get('search');

            if (currentMonth) url.searchParams.set('month', currentMonth);
            if (currentSearch) url.searchParams.set('search', currentSearch);

            @if (Auth::user()?->role === 'superadmin')
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
                        "px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200";
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
                        `<object data="${fullUrl}#toolbar=0" type="application/pdf" class="block w-full h-full min-h-[300px]"></object>`;
                } else {
                    frame.innerHTML =
                        `<div class="flex items-center justify-center w-full h-full p-2 bg-slate-50"><img src="${fullUrl}" class="object-contain max-w-full max-h-full rounded-xl" /></div>`;
                }
            } else {
                frame.innerHTML =
                    `<div class="flex items-center justify-center w-full h-full p-8 text-xs italic text-slate-400">No receipt document proof attached.</div>`;
            }

            const m = document.getElementById('detailModal');
            m.classList.remove('hidden');
            m.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        }

        function closeDetailModal() {
            const m = document.getElementById('detailModal');
            m.classList.add('hidden');
            m.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        }

        function confirmCancel(id) {
            const modal = document.getElementById('cancelModal');
            if (!modal) return;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
            const form = modal.querySelector('form');
            if (form) form.action = `/reimbursements/${id}`;
        }
    </script>
@endpush
