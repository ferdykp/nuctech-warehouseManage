@extends('layout.master')

@section('content')
    <div class="w-full space-y-6 sm:space-y-8">

        {{-- GREETING & STATUS --}}
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
            <div>
                <p class="mb-0.5 text-[10px] sm:text-xs font-bold tracking-widest uppercase text-slate-400">Dashboard
                    Overview</p>
                <h1 class="text-xl font-extrabold tracking-tight sm:text-2xl lg:text-3xl text-slate-800">
                    Welcome back, <span class="text-blue-600">{{ auth()->user()->name }}</span>
                </h1>
                <p class="mt-1 text-xs font-medium sm:text-sm text-slate-500">{{ now()->format('l, d F Y') }}</p>
            </div>
            <div
                class="self-start sm:self-auto flex items-center gap-2 px-3.5 py-1.5 sm:px-4 sm:py-2 bg-emerald-50 border border-emerald-200/80 rounded-xl shadow-xs">
                <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="text-xs font-bold text-emerald-700">System Online</span>
            </div>
        </div>

        {{-- STAT CARDS GRID --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">

            {{-- Card 1: Branches --}}
            <div
                class="p-4 transition-all bg-white border border-t-4 shadow-sm sm:p-5 rounded-2xl border-slate-100 border-t-blue-500 hover:shadow-md">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center justify-center w-9 h-9 rounded-xl bg-blue-50">
                        <i class="text-sm text-blue-600 fa-solid fa-building-circle-check"></i>
                    </div>
                    <span
                        class="text-[10px] sm:text-[11px] bg-blue-50 text-blue-700 font-bold px-2 py-0.5 sm:py-1 rounded-lg">Active</span>
                </div>
                <p class="mb-1 text-2xl font-black sm:text-3xl text-slate-800">{{ $totalBranch }}</p>
                <p class="text-xs font-bold text-slate-500">Total Branches</p>
                <p class="text-[11px] text-blue-600 font-medium mt-1">Registered locations</p>
            </div>

            {{-- Card 2: Spare Parts --}}
            <div
                class="p-4 transition-all bg-white border border-t-4 shadow-sm sm:p-5 rounded-2xl border-slate-100 border-t-violet-500 hover:shadow-md">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center justify-center w-9 h-9 rounded-xl bg-violet-50">
                        <i class="text-sm fa-solid fa-boxes-stacked text-violet-600"></i>
                    </div>
                    <span
                        class="text-[10px] sm:text-[11px] bg-violet-50 text-violet-700 font-bold px-2 py-0.5 sm:py-1 rounded-lg">{{ $totalSparepart }}
                        items</span>
                </div>
                <p class="mb-1 text-2xl font-black sm:text-3xl text-slate-800">{{ number_format($totalSparepart) }}</p>
                <p class="text-xs font-bold text-slate-500">Total Spare Parts</p>
                <p class="text-[11px] text-violet-600 font-medium mt-1">Stock across all sites</p>
            </div>

            {{-- Card 3: Machines --}}
            <div
                class="p-4 transition-all bg-white border border-t-4 shadow-sm sm:p-5 rounded-2xl border-slate-100 border-t-emerald-500 hover:shadow-md">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center justify-center w-9 h-9 rounded-xl bg-emerald-50">
                        <i class="text-sm fa-solid fa-microchip text-emerald-600"></i>
                    </div>
                    <span
                        class="text-[10px] sm:text-[11px] bg-emerald-50 text-emerald-700 font-bold px-2 py-0.5 sm:py-1 rounded-lg">98%
                        Active</span>
                </div>
                <p class="mb-1 text-2xl font-black sm:text-3xl text-slate-800">{{ $totalMachine }}</p>
                <p class="text-xs font-bold text-slate-500">Total Machines</p>
                <p class="text-[11px] text-emerald-600 font-medium mt-1">Operational units</p>
            </div>

            {{-- Card 4: Critical Stock --}}
            <div
                class="p-4 transition-all bg-white border border-t-4 shadow-sm sm:p-5 rounded-2xl border-slate-100 border-t-amber-500 hover:shadow-md">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center justify-center w-9 h-9 rounded-xl bg-amber-50">
                        <i class="text-sm fa-solid fa-triangle-exclamation text-amber-600"></i>
                    </div>
                    <span
                        class="text-[10px] sm:text-[11px] bg-amber-50 text-amber-700 font-bold px-2 py-0.5 sm:py-1 rounded-lg">Need
                        Check</span>
                </div>
                <p class="mb-1 text-2xl font-black sm:text-3xl text-slate-800">{{ $criticalStock ?? 0 }}</p>
                <p class="text-xs font-bold text-slate-500">Critical Stock</p>
                <p class="text-[11px] text-amber-600 font-medium mt-1">Below minimum levels</p>
            </div>

        </div>

        {{-- MAIN CONTENT GRID --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

            {{-- SITE GRID (2 KOLOM DI LAPTOP) --}}
            <div class="p-5 bg-white border shadow-sm sm:p-6 lg:col-span-2 rounded-2xl border-slate-100">
                <div class="flex flex-col justify-between gap-3 mb-5 sm:flex-row sm:items-center">
                    <div>
                        <h3 class="text-base font-bold text-slate-800">Quick Access Locations</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Select a site to view inventory details</p>
                    </div>
                    <a href="{{ route('sparepart.all') }}"
                        class="inline-flex items-center justify-center px-4 py-2 text-xs font-bold text-blue-600 transition-all bg-blue-50 rounded-xl hover:bg-blue-600 hover:text-white active:scale-95 shrink-0">
                        Global Inventory &rarr;
                    </a>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    @foreach (\App\Models\Site::with('branch')->get() as $site)
                        <a href="{{ route('sparepart.index', $site->slug) }}"
                            class="flex items-center gap-3 p-3.5 border border-slate-100 rounded-xl hover:border-blue-300 hover:bg-blue-50/40 transition-all group">
                            <div
                                class="flex items-center justify-center w-10 h-10 transition-all shrink-0 rounded-xl bg-slate-100 group-hover:bg-blue-600">
                                <i
                                    class="text-xs transition-all fa-solid fa-server text-slate-400 group-hover:text-white"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p
                                    class="text-xs font-bold truncate transition-colors sm:text-sm text-slate-700 group-hover:text-blue-700">
                                    {{ $site->machine_name }}</p>
                                <p
                                    class="text-[10px] sm:text-[11px] text-slate-400 font-medium uppercase tracking-wider truncate">
                                    {{ $site->branch->branch_name ?? '-' }}</p>
                            </div>
                            <i
                                class="fa-solid fa-chevron-right text-slate-300 group-hover:text-blue-500 group-hover:translate-x-0.5 transition-all text-xs shrink-0"></i>
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- SIDE PANEL --}}
            <div class="flex flex-col gap-5">

                {{-- Export Panel --}}
                <div class="bg-[#0C447C] rounded-2xl p-5 sm:p-6 shadow-md border border-white/10">
                    <p class="mb-2 text-xs font-extrabold tracking-widest text-blue-300 uppercase">Quick Reports</p>
                    <p class="mb-5 text-xs font-medium leading-relaxed text-blue-100/80">
                        Export spare part stock data for all branches to Excel in one click.
                    </p>
                    <div class="space-y-2">
                        <a href="{{ route('sparepart.all') }}"
                            class="flex items-center justify-center w-full py-2.5 sm:py-3 text-xs font-bold text-white transition-all bg-blue-600 rounded-xl hover:bg-blue-500 active:scale-95 shadow-sm">
                            <i class="mr-2 fa-solid fa-file-arrow-down"></i> Export Global
                        </a>
                        <a href="{{ route('sparepart.all') }}"
                            class="flex items-center justify-center w-full py-2.5 sm:py-3 text-xs font-bold text-blue-200 transition-all bg-white/10 rounded-xl hover:bg-white/20 active:scale-95">
                            <i class="mr-2 fa-solid fa-list"></i> View All Stock
                        </a>
                    </div>
                </div>

                {{-- Recent Activity --}}
                <div class="p-5 bg-white border shadow-sm rounded-2xl border-slate-100">
                    <p class="mb-4 text-xs font-extrabold tracking-wider uppercase text-slate-400">Recent Activity</p>
                    <div class="space-y-3">
                        <div class="flex items-start gap-3">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 mt-1 shrink-0"></span>
                            <div>
                                <p class="text-xs font-bold text-slate-700">Stock updated</p>
                                <p class="text-[10px] text-slate-400 font-medium">Today &middot; System</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
@endsection
