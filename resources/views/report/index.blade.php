@extends('layout.master')

@section('content')
    <div class="px-6 py-8 mx-auto max-w-7xl">
        <div class="overflow-hidden bg-white border shadow-sm border-slate-200 rounded-3xl">

            {{-- 1. HEADER SECTION --}}
            <div class="px-8 py-6 border-b bg-slate-50/50 border-slate-100 md:flex md:items-center md:justify-between">
                <div>
                    <div class="flex items-center gap-3 mb-1">
                        <div class="p-2 text-red-600 bg-red-100 rounded-xl">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                        <h2 class="text-2xl font-black tracking-tight uppercase text-slate-800">
                            Report Failure
                        </h2>
                    </div>
                    <p class="text-sm italic font-medium text-slate-500">Daftar rekaman kegagalan teknis dan kerusakan mesin
                        lapangan.</p>
                </div>

                {{-- Status Pills --}}
                <div class="hidden gap-4 md:flex">
                    <div class="text-right">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Total Reports</p>
                        <p class="text-xl font-black text-slate-700">{{ $report->total() }}</p>
                    </div>
                </div>
            </div>

            {{-- ========================================================================= --}}
            {{-- SEKSI BARU: ANTREAN SPAREPART RUSAK (FAILURE QUEUE) --}}
            {{-- ========================================================================= --}}
            <div class="px-8 pt-6 pb-2">
                <div class="flex items-center gap-2 mb-3">
                    <span class="relative flex w-2 h-2">
                        <span
                            class="absolute inline-flex w-full h-full rounded-full opacity-75 animate-ping bg-amber-400"></span>
                        <span class="relative inline-flex w-2 h-2 rounded-full bg-amber-500"></span>
                    </span>
                    <h3 class="text-xs font-black tracking-wider uppercase text-amber-600">
                        Antrean Menunggu Report (Kondisi: Damage dari Inventory)
                    </h3>
                </div>

                <div class="overflow-hidden border shadow-sm border-slate-100 rounded-2xl">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b bg-amber-50/40 border-slate-100">
                                <th class="p-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-left">
                                    Nama Sparepart</th>
                                <th class="p-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-left">
                                    Site</th>
                                <th class="p-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">
                                    Jumlah Rusak</th>
                                <th class="p-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">
                                    Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse($failureQueue as $item)
                                <tr class="transition-colors hover:bg-amber-50/10">
                                    <td class="p-4 font-bold text-slate-700">
                                        {{ $item->sparepart->item_name ?? 'N/A' }}
                                        <span class="block text-[10px] font-normal text-slate-400">SN:
                                            {{ $item->sparepart->serial_number ?? '-' }}</span>
                                    </td>
                                    <td class="p-4 text-xs font-semibold text-slate-600">
                                        {{ $item->site->machine_name ?? 'N/A' }}
                                    </td>
                                    <td class="p-4 font-black text-center text-red-600">
                                        {{ $item->qty }} <span
                                            class="text-xs font-normal text-slate-400">{{ $item->sparepart->uom ?? '' }}</span>
                                    </td>
                                    <td class="p-4 text-center">
                                        @if (Auth::user()->role === 'superadmin')
                                            <a href="{{ route('report.create', ['stock_id' => $item->id]) }}"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[11px] font-black text-white bg-amber-500 rounded-xl hover:bg-amber-600 shadow-sm shadow-amber-100 transition-all">
                                                <i class="fa-solid fa-file-pen"></i> PROSES REPORT
                                            </a>
                                        @else
                                            <span class="text-xs italic text-slate-400">Waiting Admin</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="p-6 text-xs italic font-medium text-center text-slate-400">
                                        <i class="mr-1 fa-solid fa-circle-check text-emerald-500"></i> Tidak ada sparepart
                                        rusak baru yang menunggu laporan resmi.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="px-8 py-2">
                <hr class="border-slate-100">
            </div>

            {{-- 2. ACTION & SEARCH TOOLBAR (DAFTAR REPORT UTAMA) --}}
            <div class="px-8 py-4">
                <div class="flex flex-col justify-between gap-6 xl:flex-row xl:items-center">

                    {{-- Button Groups --}}
                    <div class="flex flex-wrap items-center gap-3">
                        @if (Auth::user()->role === 'superadmin')
                            <a href="{{ route('report.create') }}"
                                class="flex items-center gap-2 px-5 py-3 text-xs font-black text-white transition-all bg-red-600 shadow-lg rounded-xl hover:bg-red-700 shadow-red-100">
                                <i class="fa-solid fa-plus"></i> TAMBAH ITEM MANUAL
                            </a>

                            <button id="btn-delete"
                                class="flex items-center gap-2 px-5 py-3 text-xs font-black transition-all bg-white border text-slate-600 border-slate-200 rounded-xl hover:bg-red-50 hover:text-red-600 hover:border-red-200">
                                <i class="fa-solid fa-trash-can"></i> DELETE SELECTED
                            </button>

                            <div class="h-8 w-[1px] bg-slate-200 mx-1 hidden sm:block"></div>

                            <a href="{{ route('report.export') }}"
                                class="flex items-center gap-2 px-5 py-3 text-xs font-black transition-all text-emerald-600 bg-emerald-50 rounded-xl hover:bg-emerald-600 hover:text-white">
                                <i class="text-sm fa-solid fa-file-excel"></i> EXPORT EXCEL
                            </a>
                        @endif
                    </div>

                    {{-- Search Bar --}}
                    <div class="relative w-full xl:w-80 group">
                        <i
                            class="fa-solid fa-magnifying-glass absolute left-4 top-3.5 text-slate-400 group-focus-within:text-red-500 transition-colors"></i>
                        <input type="text" id="search" name="search" data-route="{{ route('report.search') }}"
                            placeholder="Cari laporan atau site..." autocomplete="off"
                            class="w-full py-3 pr-4 text-xs font-bold transition-all border outline-none pl-11 text-slate-700 bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-red-500 focus:bg-white">
                    </div>
                </div>
            </div>

            {{-- 3. TABLE SECTION --}}
            <div class="px-8 pb-8">
                <div class="overflow-hidden border shadow-sm border-slate-100 rounded-2xl">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b bg-slate-50 border-slate-100">
                                @if (Auth::user()->role === 'superadmin')
                                    <th class="w-12 p-4 text-center">
                                        <input type="checkbox" id="select_all_id"
                                            class="w-4 h-4 text-red-600 rounded border-slate-300 focus:ring-red-500">
                                    </th>
                                @endif
                                <th
                                    class="p-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center w-16">
                                    No</th>
                                <th class="p-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-left">
                                    Site Machine</th>
                                <th class="p-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-left">
                                    Attendant / Reporter</th>
                                <th class="p-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">
                                    Failure Date</th>
                                @if (Auth::user()->role === 'superadmin')
                                    <th
                                        class="p-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">
                                        Action</th>
                                @endif
                            </tr>
                        </thead>

                        <tbody id="table-body" class="divide-y divide-slate-50">
                            @include('report.table', [
                                'data' => $report,
                                'routePrefix' => 'report',
                            ])
                        </tbody>
                    </table>
                </div>

                {{-- Pagination Info --}}
                <div class="px-2 mt-4">
                    {{ $report->links() }}
                </div>
            </div>

        </div>
    </div>
@endsection
