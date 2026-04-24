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

            {{-- 2. ACTION & SEARCH TOOLBAR --}}
            <div class="px-8 py-6">
                <div class="flex flex-col justify-between gap-6 xl:flex-row xl:items-center">

                    {{-- Button Groups --}}
                    <div class="flex flex-wrap items-center gap-3">
                        @if (Auth::user()->role === 'superadmin')
                            <a href="{{ route('report.create') }}"
                                class="flex items-center gap-2 px-5 py-3 text-xs font-black text-white transition-all bg-red-600 shadow-lg rounded-xl hover:bg-red-700 shadow-red-100">
                                <i class="fa-solid fa-plus"></i> TAMBAH ITEM
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
                                    <th class="p-4 text-center">
                                        <input type="checkbox" id="select_all_id"
                                            class="w-4 h-4 text-red-600 rounded border-slate-300 focus:ring-red-500">
                                    </th>
                                @endif
                                <th class="p-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">
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
