@extends('layout.master')

@section('content')
    <div class="w-full space-y-6">

        {{-- MAIN CARD CONTAINER --}}
        <div class="overflow-hidden bg-white border shadow-sm border-slate-200/80 rounded-2xl sm:rounded-3xl">

            {{-- 1. HEADER SECTION --}}
            <div
                class="flex flex-col gap-4 p-5 border-b sm:p-8 bg-slate-50/50 border-slate-100 md:flex-row md:items-center md:justify-between">
                <div>
                    <div class="flex items-center gap-3 mb-1">
                        <div class="p-2 text-rose-600 bg-rose-100 rounded-xl">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                        <h2 class="text-xl font-black tracking-tight uppercase sm:text-2xl text-slate-800">
                            Failure Reports
                        </h2>
                    </div>
                    <p class="text-xs font-medium sm:text-sm text-slate-500">Registry of technical failures and field
                        machinery breakdown logs.</p>
                </div>

                {{-- Status Pills --}}
                <div class="flex items-center gap-4 pt-3 border-t md:pt-0 md:border-t-0 border-slate-200 shrink-0">
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Total Reports</p>
                        <p class="text-lg font-black sm:text-xl text-slate-700">{{ $report->total() }}</p>
                    </div>
                </div>
            </div>

            {{-- 2. FAILURE QUEUE SECTION --}}
            <div class="p-5 pb-3 sm:p-8">
                <div class="flex items-center gap-2 mb-3">
                    <span class="relative flex w-2 h-2">
                        <span
                            class="absolute inline-flex w-full h-full rounded-full opacity-75 animate-ping bg-amber-400"></span>
                        <span class="relative inline-flex w-2 h-2 rounded-full bg-amber-500"></span>
                    </span>
                    <h3 class="text-xs font-black tracking-wider uppercase text-amber-600">
                        Pending Report Queue (Damaged Inventory Stock)
                    </h3>
                </div>

                <div class="overflow-hidden border border-slate-200/80 rounded-2xl">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse min-w-[650px]">
                            <thead>
                                <tr
                                    class="border-b bg-amber-50/40 border-slate-200/80 text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">
                                    <th class="px-4 sm:px-6 py-3.5">Sparepart Info</th>
                                    <th class="px-4 sm:px-6 py-3.5">Site Location</th>
                                    <th class="px-4 sm:px-6 py-3.5 text-center">Damaged Qty</th>
                                    <th class="px-4 sm:px-6 py-3.5 text-center w-36">Action</th>
                                </tr>
                            </thead>
                            <tbody class="text-xs font-medium divide-y divide-slate-100 sm:text-sm text-slate-700">
                                @forelse($failureQueue as $item)
                                    <tr class="transition-colors hover:bg-amber-50/20">
                                        <td class="px-4 sm:px-6 py-3.5 font-bold text-slate-800">
                                            {{ $item->sparepart->item_name ?? 'N/A' }}
                                            <span class="block text-[10px] font-mono text-slate-400 font-normal">
                                                SN: {{ $item->sparepart->serial_number ?? '-' }}
                                            </span>
                                        </td>
                                        <td class="px-4 sm:px-6 py-3.5 font-semibold text-slate-600">
                                            {{ $item->site->machine_name ?? 'N/A' }}
                                        </td>
                                        <td class="px-4 sm:px-6 py-3.5 font-black text-center text-rose-600">
                                            {{ $item->qty }} <span
                                                class="text-xs font-normal text-slate-400">{{ $item->sparepart->uom ?? '' }}</span>
                                        </td>
                                        <td class="px-4 sm:px-6 py-3.5 text-center">
                                            @if (Auth::user()->role === 'superadmin')
                                                <a href="{{ route('report.create', ['stock_id' => $item->id]) }}"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[11px] font-bold text-white bg-amber-500 rounded-xl hover:bg-amber-600 shadow-md shadow-amber-500/20 active:scale-95 transition-all">
                                                    <i class="fa-solid fa-file-pen"></i> PROCESS REPORT
                                                </a>
                                            @else
                                                <span class="text-xs italic text-slate-400">Waiting Admin</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4"
                                            class="p-6 text-xs italic font-medium text-center text-slate-400">
                                            <i class="mr-1 fa-solid fa-circle-check text-emerald-500"></i> No newly damaged
                                            spareparts awaiting formal breakdown logs.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="px-5 sm:px-8">
                <hr class="border-slate-100">
            </div>

            {{-- 3. TOOLBAR & SEARCH --}}
            <div class="p-5 py-4 sm:p-8">
                <div class="flex flex-col justify-between gap-4 xl:flex-row xl:items-center">

                    {{-- Button Groups --}}
                    <div class="flex flex-wrap items-center gap-2.5">
                        @if (Auth::user()->role === 'superadmin')
                            <a href="{{ route('report.create') }}"
                                class="flex items-center gap-2 px-4 py-2.5 text-xs font-bold text-white transition-all bg-rose-600 shadow-md shadow-rose-600/20 rounded-xl hover:bg-rose-700 active:scale-95">
                                <i class="text-xs fa-solid fa-plus"></i> ADD MANUAL ENTRY
                            </a>

                            <button id="btn-delete"
                                class="flex items-center gap-2 px-4 py-2.5 text-xs font-bold transition-all bg-white border text-slate-600 border-slate-200 rounded-xl hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 active:scale-95">
                                <i class="fa-solid fa-trash-can"></i> DELETE SELECTED
                            </button>

                            <div class="h-6 w-[1px] bg-slate-200 mx-1 hidden sm:block"></div>

                            <a href="{{ route('report.export') }}"
                                class="flex items-center gap-2 px-4 py-2.5 text-xs font-bold transition-all text-emerald-600 bg-emerald-50 rounded-xl hover:bg-emerald-600 hover:text-white active:scale-95">
                                <i class="text-sm fa-solid fa-file-excel"></i> EXPORT EXCEL
                            </a>
                        @endif
                    </div>

                    {{-- Search Bar --}}
                    <div class="relative w-full xl:w-80">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                            <i class="text-xs fa-solid fa-magnifying-glass"></i>
                        </div>
                        <input type="text" id="search" name="search" data-route="{{ route('report.search') }}"
                            placeholder="Search report or site..." autocomplete="off"
                            class="w-full py-2.5 pl-10 pr-3.5 text-xs sm:text-sm font-medium border outline-none text-slate-700 bg-slate-50 border-slate-200 rounded-xl focus:ring-4 focus:ring-rose-500/10 focus:border-rose-500 focus:bg-white transition-all">
                    </div>
                </div>
            </div>

            {{-- 4. TABLE SECTION --}}
            <div class="p-5 pt-0 sm:p-8">
                <div class="overflow-hidden border border-slate-200/80 rounded-2xl">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse min-w-[700px]">
                            <thead>
                                <tr
                                    class="border-b bg-slate-100/70 border-slate-200/80 text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">
                                    @if (Auth::user()->role === 'superadmin')
                                        <th class="w-12 px-4 sm:px-6 py-3.5 text-center">
                                            <input type="checkbox" id="select_all_id"
                                                class="w-4 h-4 rounded text-rose-600 border-slate-300 focus:ring-rose-500">
                                        </th>
                                    @endif
                                    <th class="px-4 sm:px-6 py-3.5 text-center w-14">No</th>
                                    <th class="px-4 sm:px-6 py-3.5">Site Machine</th>
                                    <th class="px-4 sm:px-6 py-3.5">Attendant / Reporter</th>
                                    <th class="px-4 sm:px-6 py-3.5 text-center">Failure Date</th>
                                    @if (Auth::user()->role === 'superadmin')
                                        <th class="px-4 sm:px-6 py-3.5 text-center w-28">Action</th>
                                    @endif
                                </tr>
                            </thead>

                            <tbody id="table-body"
                                class="text-xs font-medium divide-y divide-slate-100 sm:text-sm text-slate-700">
                                @include('report.table', [
                                    'data' => $report,
                                    'routePrefix' => 'report',
                                ])
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Pagination Info --}}
                <div class="mt-4">
                    {{ $report->links() }}
                </div>
            </div>

        </div>
    </div>
@endsection
