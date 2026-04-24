@extends('layout.master')

@section('content')
    <div class="min-h-screen px-6 py-8 bg-[#f4f6f9]">

        {{-- GREETING --}}
        <div class="flex flex-wrap items-center justify-between gap-3 mb-8">
            <div>
                <p class="mb-1 text-xs tracking-wider uppercase text-slate-400">Dashboard Overview</p>
                <h1 class="text-2xl font-bold text-slate-800">
                    Welcome back, <span class="text-blue-600">{{ auth()->user()->name }}</span>
                </h1>
                <p class="mt-1 text-sm text-slate-500">{{ now()->format('l, d F Y') }}</p>
            </div>
            <div class="flex items-center gap-2 px-4 py-2 border bg-emerald-50 border-emerald-200 rounded-xl">
                <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="text-xs font-semibold text-emerald-700">System Online</span>
            </div>
        </div>

        {{-- STAT CARDS --}}
        <div class="grid grid-cols-2 gap-4 mb-8 lg:grid-cols-4">

            <div
                class="p-5 transition-all bg-white border border-t-4 shadow-sm rounded-2xl border-slate-100 border-t-blue-500 hover:shadow-md">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center justify-center w-9 h-9 rounded-xl bg-blue-50">
                        <i class="text-sm text-blue-600 fa-solid fa-building-circle-check"></i>
                    </div>
                    <span class="text-[11px] bg-blue-50 text-blue-700 font-semibold px-2 py-1 rounded-lg">Active</span>
                </div>
                <p class="mb-1 text-3xl font-black text-slate-800">{{ $totalBranch }}</p>
                <p class="text-xs font-semibold text-slate-500">Total Branches</p>
                <p class="text-[11px] text-blue-600 mt-2">Registered locations</p>
            </div>

            <div
                class="p-5 transition-all bg-white border border-t-4 shadow-sm rounded-2xl border-slate-100 border-t-violet-500 hover:shadow-md">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center justify-center w-9 h-9 rounded-xl bg-violet-50">
                        <i class="text-sm fa-solid fa-boxes-stacked text-violet-600"></i>
                    </div>
                    <span
                        class="text-[11px] bg-violet-50 text-violet-700 font-semibold px-2 py-1 rounded-lg">{{ $totalSparepart }}
                        items</span>
                </div>
                <p class="mb-1 text-3xl font-black text-slate-800">{{ number_format($totalSparepart) }}</p>
                <p class="text-xs font-semibold text-slate-500">Total Spare Parts</p>
                <p class="text-[11px] text-violet-600 mt-2">Stock across all sites</p>
            </div>

            <div
                class="p-5 transition-all bg-white border border-t-4 shadow-sm rounded-2xl border-slate-100 border-t-emerald-500 hover:shadow-md">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center justify-center w-9 h-9 rounded-xl bg-emerald-50">
                        <i class="text-sm fa-solid fa-microchip text-emerald-600"></i>
                    </div>
                    <span class="text-[11px] bg-emerald-50 text-emerald-700 font-semibold px-2 py-1 rounded-lg">98%
                        Active</span>
                </div>
                <p class="mb-1 text-3xl font-black text-slate-800">{{ $totalMachine }}</p>
                <p class="text-xs font-semibold text-slate-500">Total Machines</p>
                <p class="text-[11px] text-emerald-600 mt-2">Operational units</p>
            </div>

            <div
                class="p-5 transition-all bg-white border border-t-4 shadow-sm rounded-2xl border-slate-100 border-t-amber-500 hover:shadow-md">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center justify-center w-9 h-9 rounded-xl bg-amber-50">
                        <i class="text-sm fa-solid fa-triangle-exclamation text-amber-600"></i>
                    </div>
                    <span class="text-[11px] bg-amber-50 text-amber-700 font-semibold px-2 py-1 rounded-lg">Need
                        Check</span>
                </div>
                <p class="mb-1 text-3xl font-black text-slate-800">{{ $criticalStock ?? 0 }}</p>
                <p class="text-xs font-semibold text-slate-500">Critical Stock</p>
                <p class="text-[11px] text-amber-600 mt-2">Below minimum levels</p>
            </div>

        </div>

        {{-- MAIN CONTENT --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

            {{-- SITE GRID --}}
            <div class="p-6 bg-white border shadow-sm lg:col-span-2 rounded-2xl border-slate-100">
                <div class="flex items-center justify-between mb-5">
                    <div>
                        <h3 class="text-sm font-bold text-slate-800">Quick Access Locations</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Select a site to view inventory details</p>
                    </div>
                    <a href="{{ route('sparepart.all') }}"
                        class="px-4 py-2 text-xs font-bold text-blue-600 transition-all bg-blue-50 rounded-xl hover:bg-blue-600 hover:text-white">
                        Global Inventory →
                    </a>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    @foreach (\App\Models\Site::with('branch')->get() as $site)
                        <a href="{{ route('sparepart.index', $site->slug) }}"
                            class="flex items-center gap-3 p-3.5 border border-slate-100 rounded-xl hover:border-blue-300 hover:bg-blue-50/50 transition-all group">
                            <div
                                class="flex items-center justify-center flex-shrink-0 transition-all w-9 h-9 rounded-xl bg-slate-100 group-hover:bg-blue-600">
                                <i
                                    class="text-xs transition-all fa-solid fa-server text-slate-400 group-hover:text-white"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p
                                    class="text-sm font-bold truncate transition-colors text-slate-700 group-hover:text-blue-700">
                                    {{ $site->machine_name }}</p>
                                <p class="text-[11px] text-slate-400 uppercase tracking-wider truncate">
                                    {{ $site->branch->branch_name }}</p>
                            </div>
                            <i
                                class="fa-solid fa-chevron-right text-slate-200 group-hover:text-blue-500 group-hover:translate-x-0.5 transition-all text-xs"></i>
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- SIDE PANEL --}}
            <div class="flex flex-col gap-5">

                {{-- Export Panel --}}
                <div class="bg-[#0C447C] rounded-2xl p-6">
                    <p class="mb-3 text-xs font-bold tracking-widest text-blue-300 uppercase">Quick Reports</p>
                    <p class="mb-5 text-xs leading-relaxed text-blue-200">
                        Export spare part stock data for all branches to Excel in one click.
                    </p>
                    <a href="{{ route('sparepart.all') }}"
                        class="flex items-center justify-center w-full py-3 mb-2 text-xs font-bold text-white transition-all bg-blue-500 rounded-xl hover:bg-blue-400">
                        <i class="mr-2 fa-solid fa-file-arrow-down"></i> Export Global
                    </a>
                    <a href="{{ route('sparepart.all') }}"
                        class="flex items-center justify-center w-full py-3 text-xs font-bold text-blue-200 transition-all bg-white/10 rounded-xl hover:bg-white/20">
                        <i class="mr-2 fa-solid fa-list"></i> View All Stock
                    </a>
                </div>

                {{-- Recent Activity --}}
                <div class="p-5 bg-white border shadow-sm rounded-2xl border-slate-100">
                    <p class="mb-4 text-xs font-bold tracking-wider uppercase text-slate-400">Recent Activity</p>
                    <div class="space-y-3">
                        <div class="flex items-start gap-3">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 mt-1.5 flex-shrink-0"></span>
                            <div>
                                <p class="text-xs font-semibold text-slate-700">Stock updated</p>
                                <p class="text-[11px] text-slate-400">Today · System</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
@endsection
