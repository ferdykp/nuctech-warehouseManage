@extends('layout.master')

@section('title', 'System Command Center')

@section('content')
    <div class="w-full space-y-6">

        {{-- ============ 1. HEADER & STATUS ============ --}}
        <div
            class="flex flex-col justify-between gap-4 p-6 bg-white border shadow-sm sm:flex-row sm:items-end rounded-3xl border-slate-200/80">
            <div>
                <p class="mb-1 text-[10px] font-extrabold tracking-widest uppercase text-blue-500">
                    Command Center Overview
                </p>
                <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl text-slate-900">
                    Welcome back, <span class="text-blue-600">{{ auth()->user()->name }}</span> 👋
                </h1>
                <p class="mt-1 text-xs font-semibold sm:text-sm text-slate-500">
                    Here is your operational summary for <span class="text-slate-700">{{ now()->format('l, d F Y') }}</span>.
                </p>
            </div>
            <div class="flex items-center gap-3 shrink-0">
                <div
                    class="flex items-center gap-2 px-4 py-2 border bg-emerald-50 border-emerald-200/80 rounded-xl shadow-2xs">
                    <span class="relative flex w-2.5 h-2.5">
                        <span
                            class="absolute inline-flex w-full h-full rounded-full opacity-75 animate-ping bg-emerald-400"></span>
                        <span class="relative inline-flex w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                    </span>
                    <span class="text-xs font-bold tracking-wide uppercase text-emerald-700">System Online</span>
                </div>
            </div>
        </div>

        {{-- ============ 2. METRICS GRID ============ --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {{-- Metric 1: Sites --}}
            <div
                class="p-5 transition-all bg-white border border-t-4 shadow-sm rounded-3xl border-slate-200/80 border-t-blue-500 hover:shadow-md group">
                <div class="flex items-start justify-between mb-4">
                    <div
                        class="flex items-center justify-center w-10 h-10 text-blue-600 transition-colors bg-blue-50 rounded-xl group-hover:bg-blue-600 group-hover:text-white">
                        <i class="text-base fa-solid fa-building-circle-check"></i>
                    </div>
                    <span
                        class="text-[10px] font-bold tracking-wider text-blue-700 uppercase bg-blue-50 px-2.5 py-1 rounded-lg border border-blue-100">Active</span>
                </div>
                <p class="mb-0.5 text-3xl font-black text-slate-800">{{ $totalBranch ?? 0 }}</p>
                <p class="text-xs font-bold text-slate-500">Operational Sites</p>
            </div>

            {{-- Metric 2: Workforce --}}
            <div
                class="p-5 transition-all bg-white border border-t-4 shadow-sm rounded-3xl border-slate-200/80 border-t-indigo-500 hover:shadow-md group">
                <div class="flex items-start justify-between mb-4">
                    <div
                        class="flex items-center justify-center w-10 h-10 text-indigo-600 transition-colors bg-indigo-50 rounded-xl group-hover:bg-indigo-600 group-hover:text-white">
                        <i class="text-base fa-solid fa-users"></i>
                    </div>
                    <span
                        class="text-[10px] font-bold tracking-wider text-indigo-700 uppercase bg-indigo-50 px-2.5 py-1 rounded-lg border border-indigo-100">Personnel</span>
                </div>
                <p class="mb-0.5 text-3xl font-black text-slate-800">{{ $totalEmployee ?? 0 }}</p>
                <p class="text-xs font-bold text-slate-500">Total Employees</p>
            </div>

            {{-- Metric 3: Inventory --}}
            <div
                class="p-5 transition-all bg-white border border-t-4 shadow-sm rounded-3xl border-slate-200/80 border-t-violet-500 hover:shadow-md group">
                <div class="flex items-start justify-between mb-4">
                    <div
                        class="flex items-center justify-center w-10 h-10 transition-colors bg-violet-50 rounded-xl group-hover:bg-violet-600 group-hover:text-white text-violet-600">
                        <i class="text-base fa-solid fa-boxes-stacked"></i>
                    </div>
                    <span
                        class="text-[10px] font-bold tracking-wider text-violet-700 uppercase bg-violet-50 px-2.5 py-1 rounded-lg border border-violet-100">Global</span>
                </div>
                <p class="mb-0.5 text-3xl font-black text-slate-800">{{ number_format($totalSparepart ?? 0) }}</p>
                <p class="text-xs font-bold text-slate-500">Total Spare Parts</p>
            </div>

            {{-- Metric 4: Alerts --}}
            <div
                class="p-5 transition-all bg-white border border-t-4 shadow-sm rounded-3xl border-slate-200/80 border-t-rose-500 hover:shadow-md group">
                <div class="flex items-start justify-between mb-4">
                    <div
                        class="flex items-center justify-center w-10 h-10 transition-colors bg-rose-50 rounded-xl group-hover:bg-rose-600 group-hover:text-white text-rose-600">
                        <i class="text-base fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <span
                        class="text-[10px] font-bold tracking-wider text-rose-700 uppercase bg-rose-50 px-2.5 py-1 rounded-lg border border-rose-100">Low
                        Stock</span>
                </div>
                <p class="mb-0.5 text-3xl font-black text-slate-800">{{ $criticalStock ?? 0 }}</p>
                <p class="text-xs font-bold text-slate-500">Critical Items</p>
            </div>
        </div>

        {{-- ============ 3. MAIN WORKSPACE (3 Columns Layout) ============ --}}
        <div class="grid items-start grid-cols-1 gap-6 lg:grid-cols-3">

            {{-- LEFT COLUMN (WIDER - Span 2) --}}
            <div class="space-y-6 lg:col-span-2">

                {{-- WIDGET A: TODAY'S WORKFORCE (HRIS Focus) --}}
                <div class="p-6 bg-white border shadow-sm border-slate-200/80 rounded-3xl">
                    <div class="flex flex-col justify-between gap-3 mb-5 sm:flex-row sm:items-center">
                        <div>
                            <h3 class="text-base font-extrabold text-slate-800">Today's Active Workforce</h3>
                            <p class="text-xs font-medium text-slate-500 mt-0.5">{{ $todaysSchedules->count() }} employees
                                scheduled today.</p>
                        </div>
                        <a href="{{ route('schedule.index') }}"
                            class="inline-flex items-center justify-center px-4 py-2 text-xs font-bold text-indigo-700 transition-all border border-indigo-100 bg-indigo-50 rounded-xl hover:bg-indigo-600 hover:text-white active:scale-95 shrink-0">
                            View All Rotas &rarr;
                        </a>
                    </div>

                    <div class="overflow-hidden border rounded-xl border-slate-100">
                        <div class="overflow-y-auto max-h-[360px]">
                            <table class="w-full text-left border-collapse">
                                <thead class="sticky top-0 z-10 bg-slate-50">
                                    <tr
                                        class="bg-slate-50 text-[10px] font-extrabold text-slate-500 uppercase tracking-wider border-b border-slate-100">
                                        <th class="px-4 py-3">Employee</th>
                                        <th class="px-4 py-3">Site Location</th>
                                        <th class="px-4 py-3 text-center">Shift Schedule</th>
                                    </tr>
                                </thead>
                                <tbody class="text-xs font-medium divide-y sm:text-sm divide-slate-100 text-slate-700">
                                    @forelse($todaysSchedules ?? [] as $sched)
                                        <tr class="hover:bg-slate-50/50">
                                            <td class="px-4 py-3 font-bold text-slate-800">
                                                <div class="flex items-center gap-2">
                                                    <div
                                                        class="flex items-center justify-center text-xs font-bold text-blue-700 bg-blue-100 rounded-full w-7 h-7 shrink-0">
                                                        {{ strtoupper(substr($sched->employee->name, 0, 1)) }}
                                                    </div>
                                                    {{ $sched->employee->name }}
                                                </div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span
                                                    class="inline-flex items-center gap-1.5 px-2 py-1 text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200 rounded-md">
                                                    <i class="fa-solid fa-location-dot text-slate-400"></i>
                                                    {{ $sched->employee->site->machine_name ?? '-' }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                @php
                                                    $shiftName = strtolower($sched->shift->shift_name);
                                                    $badgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                                    if (
                                                        str_contains($shiftName, '1') ||
                                                        str_contains($shiftName, 'hour')
                                                    ) {
                                                        $badgeClass = 'bg-blue-50 text-blue-700 border-blue-200';
                                                    } elseif (str_contains($shiftName, '2')) {
                                                        $badgeClass = 'bg-amber-50 text-amber-700 border-amber-200';
                                                    } elseif (str_contains($shiftName, '3')) {
                                                        $badgeClass = 'bg-purple-50 text-purple-700 border-purple-200';
                                                    }
                                                @endphp
                                                <span
                                                    class="px-2.5 py-1 text-[10px] font-bold border rounded-lg {{ $badgeClass }}">
                                                    <i class="mr-1 fa-regular fa-clock"></i>{{ $sched->shift->shift_name }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="px-4 py-10 text-center text-slate-400">
                                                <i class="mb-2 text-3xl opacity-50 fa-solid fa-bed"></i>
                                                <p class="text-sm font-bold">No shifts scheduled for today.</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- WIDGET B: INVENTORY QUICK ACCESS (Warehouse Focus) --}}
                <div class="p-6 bg-white border shadow-sm h-[455px] border-slate-200/80 rounded-3xl">
                    <div class="flex flex-col justify-between gap-3 mb-5 sm:flex-row sm:items-center">
                        <div>
                            <h3 class="text-base font-extrabold text-slate-800">Operational Sites & Inventory</h3>
                            <p class="text-xs font-medium text-slate-500 mt-0.5">Select a site branch to manage
                                warehouse
                                stocks.</p>
                        </div>
                        <a href="{{ route('sparepart.all') }}"
                            class="inline-flex items-center justify-center px-4 py-2 text-xs font-bold text-blue-700 transition-all border border-blue-100 bg-blue-50 rounded-xl hover:bg-blue-600 hover:text-white active:scale-95 shrink-0">
                            Global Inventory &rarr;
                        </a>
                    </div>

                    <div class="grid grid-cols-1 gap-3 pr-2 overflow-y-auto lg:grid-cols-2 max-h-[360px]">
                        @foreach (\App\Models\Site::with('branch')->get() as $site)
                            <a href="{{ route('sparepart.index', $site->slug) }}"
                                class="flex items-center gap-3.5 p-3.5 border border-slate-200/70 rounded-2xl hover:border-blue-300 hover:bg-blue-50/50 transition-all group shadow-2xs">
                                <div
                                    class="flex items-center justify-center transition-all w-11 h-11 shrink-0 rounded-xl bg-slate-100 group-hover:bg-blue-600 group-hover:shadow-md group-hover:shadow-blue-600/20">
                                    <i
                                        class="text-sm transition-all fa-solid fa-server text-slate-400 group-hover:text-white"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p
                                        class="text-sm font-extrabold truncate transition-colors text-slate-800 group-hover:text-blue-700">
                                        {{ $site->machine_name }}
                                    </p>
                                    <p
                                        class="text-[10px] text-slate-500 font-bold uppercase tracking-wider truncate mt-0.5">
                                        {{ $site->branch->branch_name ?? 'Branch HQ' }}
                                    </p>
                                </div>
                                <i
                                    class="text-xs transition-all fa-solid fa-chevron-right text-slate-300 group-hover:text-blue-500 group-hover:translate-x-1 shrink-0"></i>
                            </a>
                        @endforeach
                    </div>
                </div>

            </div>

            {{-- RIGHT COLUMN (SIDE PANEL - Span 1) --}}
            <div class="flex flex-col gap-6">

                {{-- WIDGET C: QUICK COMMAND CENTER (Dark UI) --}}
                <div class="relative p-6 overflow-hidden border shadow-xl bg-slate-900 rounded-3xl border-slate-800">
                    <!-- Accent background graphic -->
                    <div class="absolute top-0 right-0 p-4 opacity-10">
                        <i class="text-blue-400 fa-solid fa-bolt text-8xl"></i>
                    </div>

                    <div class="relative z-10">
                        <p class="mb-1 text-[10px] font-black tracking-widest text-blue-400 uppercase">Quick Actions</p>
                        <p class="mb-6 text-xs font-medium leading-relaxed text-slate-400">
                            Execute essential administrative tasks instantly from here.
                        </p>
                        <div class="space-y-3">
                            <a href="{{ route('sparepart.all') }}"
                                class="flex items-center justify-center w-full py-3 text-xs font-bold transition-all shadow-sm text-slate-900 bg-emerald-400 rounded-xl hover:bg-emerald-300 active:scale-95">
                                <i class="mr-2 fa-solid fa-file-excel"></i> Export Global Stock
                            </a>
                            <a href="{{ route('schedule.index') }}"
                                class="flex items-center justify-center w-full py-3 text-xs font-bold text-white transition-all bg-blue-600 shadow-sm rounded-xl hover:bg-blue-500 active:scale-95">
                                <i class="mr-2 fa-solid fa-wand-magic-sparkles"></i> Generate Schedules
                            </a>
                            <a href="{{ route('attendance.index') }}"
                                class="flex items-center justify-center w-full py-3 text-xs font-bold text-white transition-all bg-indigo-600 shadow-sm rounded-xl hover:bg-indigo-500 active:scale-95">
                                <i class="mr-2 fa-solid fa-calendar-check"></i> Fill Attendances
                            </a>
                            @if (auth()->user()->role === 'superadmin')
                                <a href="{{ route('employee.index') }}"
                                    class="flex items-center justify-center w-full py-3 text-xs font-bold transition-all border text-slate-300 bg-slate-800 border-slate-700 rounded-xl hover:bg-slate-700 hover:text-white active:scale-95">
                                    <i class="mr-2 fa-solid fa-user-plus"></i> Add New Employee
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- WIDGET D: CRITICAL ALERTS --}}
                <div class="p-6 bg-white border shadow-sm border-slate-200/80 rounded-3xl">
                    <p class="mb-4 text-[10px] font-black tracking-widest uppercase text-slate-400">System Alerts</p>
                    <div class="space-y-4">
                        @if (($criticalStock ?? 0) > 0)
                            <div class="flex items-start gap-3 p-3 border border-rose-100 bg-rose-50 rounded-xl">
                                <div
                                    class="flex items-center justify-center w-8 h-8 bg-white rounded-lg shadow-sm shrink-0 text-rose-600">
                                    <i class="text-xs fa-solid fa-triangle-exclamation"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-extrabold text-rose-700">Inventory Alert</p>
                                    <p class="text-[10px] text-rose-600/80 font-medium mt-0.5 leading-tight">
                                        {{ $criticalStock }} item(s) have fallen below the minimum safety threshold.
                                    </p>
                                </div>
                            </div>
                        @else
                            <div class="flex items-start gap-3">
                                <div
                                    class="flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-50 shrink-0 text-emerald-600">
                                    <i class="text-xs fa-solid fa-check"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-extrabold text-slate-700">Inventory Optimal</p>
                                    <p class="text-[10px] text-slate-500 font-medium mt-0.5">All stock items are well
                                        above
                                        minimum limits.</p>
                                </div>
                            </div>
                        @endif

                        <div class="flex items-start gap-3">
                            <div
                                class="flex items-center justify-center w-8 h-8 text-blue-600 rounded-lg bg-blue-50 shrink-0">
                                <i class="text-xs fa-solid fa-calendar-days"></i>
                            </div>
                            <div>
                                <p class="text-xs font-extrabold text-slate-700">Schedules Updated</p>
                                <p class="text-[10px] text-slate-500 font-medium mt-0.5">Rotas for
                                    {{ now()->format('F Y') }} are active.</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- WIDGET E: UPCOMING HOLIDAYS (Filler / Info) --}}
                <div class="p-6 bg-white border shadow-sm border-slate-200/80 rounded-3xl">
                    @forelse ($upcomingHolidays ?? [] as $holiday)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-2 h-2 rounded-full bg-rose-500"></div>
                                <span class="text-xs font-bold text-slate-700">
                                    {{ $holiday['name'] }}
                                </span>
                            </div>

                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                                {{ $holiday['date'] }}
                            </span>
                        </div>
                    @empty
                        <div class="py-6 text-center border border-dashed rounded-xl border-slate-200 bg-slate-50">
                            <div
                                class="flex items-center justify-center w-12 h-12 mx-auto mb-3 rounded-full bg-emerald-100 text-emerald-600">
                                <i class="text-lg fa-solid fa-calendar-check"></i>
                            </div>

                            <p class="text-sm font-bold text-slate-700">
                                No Public Holidays
                            </p>

                            <p class="mt-1 text-xs text-slate-500">
                                There are no national public holidays remaining in
                                <span class="font-semibold">{{ now()->format('F') }}</span>.
                            </p>
                        </div>
                    @endforelse
                </div>


            </div>

        </div>
    </div>
@endsection
