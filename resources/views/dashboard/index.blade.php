@extends('layout.master')

@section('title', 'Dashboard Command Center')

@section('content')
    <div class="space-y-6">

        {{-- 1. WELCOME BANNER --}}
        <div
            class="flex flex-col items-start justify-between gap-6 p-6 bg-white border shadow-xs sm:p-8 border-slate-200/80 rounded-3xl md:flex-row md:items-center">
            <div>
                <div
                    class="inline-flex items-center gap-2 px-3 py-1 mb-2 text-xs font-bold text-blue-700 border border-blue-100 rounded-full bg-blue-50">
                    <i class="fa-solid fa-chart-line text-[10px]"></i> Operational Command Center
                </div>
                <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl text-slate-900">
                    Welcome back, <span class="text-blue-600">{{ auth()->user()->username ?? 'User' }}</span> 👋
                </h1>
                <p class="mt-1 text-xs font-medium sm:text-sm text-slate-500">
                    Here is your live operational overview for <strong
                        class="text-slate-700">{{ now()->format('l, d F Y') }}</strong>.
                </p>
            </div>

            <div class="flex items-center gap-3 shrink-0">
                <div class="flex items-center gap-2.5 px-4 py-2.5 bg-emerald-50 border border-emerald-200 rounded-2xl">
                    <span class="relative flex h-2.5 w-2.5">
                        <span
                            class="absolute inline-flex w-full h-full rounded-full opacity-75 animate-ping bg-emerald-400"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                    </span>
                    <span class="text-xs font-bold tracking-wide uppercase text-emerald-800">All Systems Active</span>
                </div>
            </div>
        </div>

        {{-- 2. METRIC CARDS --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">

            {{-- Card 1: Operational Sites --}}
            <div class="p-5 transition-all bg-white border shadow-xs border-slate-200/80 rounded-3xl hover:border-blue-300">
                <div class="flex items-center justify-between mb-3">
                    <div
                        class="flex items-center justify-center w-10 h-10 text-base font-bold text-blue-600 rounded-2xl bg-blue-50">
                        <i class="fa-solid fa-building"></i>
                    </div>
                    <span
                        class="px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-blue-700 bg-blue-50 rounded-lg border border-blue-100">
                        Active
                    </span>
                </div>
                <p class="text-3xl font-black tracking-tight text-slate-900">{{ $totalBranch ?? 0 }}</p>
                <p class="mt-1 text-xs font-semibold text-slate-500">Operational Branches</p>
            </div>

            {{-- Card 2: Employees --}}
            <div
                class="p-5 transition-all bg-white border shadow-xs border-slate-200/80 rounded-3xl hover:border-indigo-300">
                <div class="flex items-center justify-between mb-3">
                    <div
                        class="flex items-center justify-center w-10 h-10 text-base font-bold text-indigo-600 rounded-2xl bg-indigo-50">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <span
                        class="px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-indigo-700 bg-indigo-50 rounded-lg border border-indigo-100">
                        Personnel
                    </span>
                </div>
                <p class="text-3xl font-black tracking-tight text-slate-900">{{ $totalEmployee ?? 0 }}</p>
                <p class="mt-1 text-xs font-semibold text-slate-500">Registered Personnel</p>
            </div>

            {{-- Card 3: Spare parts --}}
            <div
                class="p-5 transition-all bg-white border shadow-xs border-slate-200/80 rounded-3xl hover:border-violet-300">
                <div class="flex items-center justify-between mb-3">
                    <div
                        class="flex items-center justify-center w-10 h-10 text-base font-bold rounded-2xl bg-violet-50 text-violet-600">
                        <i class="fa-solid fa-boxes-stacked"></i>
                    </div>
                    <span
                        class="px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-violet-700 bg-violet-50 rounded-lg border border-violet-100">
                        Global
                    </span>
                </div>
                <p class="text-3xl font-black tracking-tight text-slate-900">{{ number_format($totalSparepart ?? 0) }}</p>
                <p class="mt-1 text-xs font-semibold text-slate-500">Total Spare Parts</p>
            </div>

            {{-- Card 4: Low Stock --}}
            <div class="p-5 transition-all bg-white border shadow-xs border-slate-200/80 rounded-3xl hover:border-rose-300">
                <div class="flex items-center justify-between mb-3">
                    <div
                        class="flex items-center justify-center w-10 h-10 text-base font-bold rounded-2xl bg-rose-50 text-rose-600">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <span
                        class="px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-rose-700 bg-rose-50 rounded-lg border border-rose-100">
                        Attention
                    </span>
                </div>
                <p class="text-3xl font-black tracking-tight text-slate-900">{{ $criticalStock ?? 0 }}</p>
                <p class="mt-1 text-xs font-semibold text-slate-500">Critical Stock Items</p>
            </div>

        </div>

        {{-- 3. MAIN WORKSPACE GRID (2/3 Left, 1/3 Right) --}}
        <div class="grid items-start grid-cols-1 gap-6 lg:grid-cols-3">

            {{-- LEFT COLUMN: TABLES & WIDGETS (Span 2) --}}
            <div class="space-y-6 lg:col-span-2">

                {{-- TODAY'S WORKFORCE TABLE --}}
                <div class="p-6 bg-white border shadow-xs border-slate-200/80 rounded-3xl">
                    <div class="flex flex-col justify-between gap-3 mb-5 sm:flex-row sm:items-center">
                        <div>
                            <h3 class="text-base font-extrabold text-slate-800">Today's Active Shifts</h3>
                            <p class="text-xs font-medium text-slate-500">Scheduled employee shifts for today.</p>
                        </div>
                        <a href="{{ route('schedule.index') }}"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold text-blue-600 bg-blue-50 border border-blue-100 rounded-xl hover:bg-blue-600 hover:text-white transition-all shrink-0">
                            <span>View Full Schedule</span>
                            <i class="fa-solid fa-arrow-right text-[10px]"></i>
                        </a>
                    </div>

                    {{-- Table Responsive Wrapper --}}
                    <div class="overflow-x-auto border rounded-2xl border-slate-100">
                        <table class="w-full text-xs text-left">
                            <thead
                                class="bg-slate-50 border-b border-slate-100 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                <tr>
                                    <th class="px-4 py-3">Employee</th>
                                    <th class="px-4 py-3">Site Location</th>
                                    <th class="px-4 py-3 text-center">Shift Schedule</th>
                                </tr>
                            </thead>
                            <tbody class="font-medium divide-y divide-slate-100 text-slate-700">
                                @forelse($todaysSchedules ?? [] as $sched)
                                    <tr class="transition-colors hover:bg-slate-50/60">
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-2.5">
                                                <div
                                                    class="flex items-center justify-center text-xs font-bold text-white bg-blue-600 rounded-full w-7 h-7 shrink-0">
                                                    {{ strtoupper(substr($sched->employee->name ?? 'E', 0, 1)) }}
                                                </div>
                                                <span
                                                    class="font-bold text-slate-800">{{ $sched->employee->name ?? 'N/A' }}</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[11px] font-semibold bg-slate-100 text-slate-600 rounded-lg">
                                                <i class="fa-solid fa-location-dot text-slate-400"></i>
                                                {{ $sched->employee->site->machine_name ?? 'Unassigned' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span
                                                class="px-2.5 py-1 text-[11px] font-bold rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                <i
                                                    class="mr-1 fa-regular fa-clock"></i>{{ $sched->shift->shift_name ?? 'Standard' }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-4 py-8 text-center text-slate-400">
                                            <i class="mb-2 text-2xl fa-solid fa-calendar-xmark text-slate-300"></i>
                                            <p class="text-xs font-bold text-slate-500">No shifts scheduled for today.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- SITE & INVENTORY DIRECTORY --}}
                <div class="p-6 bg-white border shadow-xs border-slate-200/80 rounded-3xl">
                    <div class="flex flex-col justify-between gap-3 mb-5 sm:flex-row sm:items-center">
                        <div>
                            <h3 class="text-base font-extrabold text-slate-800">Machine Sites Directory</h3>
                            <p class="text-xs font-medium text-slate-500">Select a location to manage local spare parts.</p>
                        </div>
                        <a href="{{ route('sparepart.all') }}"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold text-slate-700 bg-slate-100 border border-slate-200 rounded-xl hover:bg-slate-200 transition-all shrink-0">
                            <span>All Spareparts</span>
                            <i class="fa-solid fa-arrow-right text-[10px]"></i>
                        </a>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-[320px] overflow-y-auto pr-1">
                        @foreach (\App\Models\Site::with('branch')->get() as $site)
                            <a href="{{ route('sparepart.index', $site->slug) }}"
                                class="p-3.5 rounded-2xl border border-slate-200/80 hover:border-blue-500 hover:bg-blue-50/40 transition-all flex items-center gap-3 group">
                                <div
                                    class="flex items-center justify-center w-10 h-10 text-sm font-bold transition-colors rounded-xl bg-slate-100 group-hover:bg-blue-600 text-slate-500 group-hover:text-white shrink-0">
                                    <i class="fa-solid fa-server"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p
                                        class="text-xs font-extrabold truncate transition-colors text-slate-800 group-hover:text-blue-700">
                                        {{ $site->machine_name }}
                                    </p>
                                    <p class="text-[10px] font-semibold text-slate-400 uppercase truncate mt-0.5">
                                        {{ $site->branch->branch_name ?? 'Branch HQ' }}
                                    </p>
                                </div>
                                <i
                                    class="text-xs transition-colors fa-solid fa-chevron-right text-slate-300 group-hover:text-blue-500"></i>
                            </a>
                        @endforeach
                    </div>
                </div>

            </div>

            {{-- RIGHT COLUMN: ACTIONS & ALERTS (Span 1) --}}
            <div class="space-y-6">

                {{-- QUICK ACTION PANEL --}}
                <div class="p-6 text-white border shadow-xl bg-slate-900 border-slate-800 rounded-3xl">
                    <div class="flex items-center gap-2 mb-1 text-xs font-bold tracking-wider text-blue-400 uppercase">
                        <i class="fa-solid fa-bolt text-[10px]"></i> Quick Actions
                    </div>
                    <p class="mb-5 text-xs font-medium text-slate-400">Frequently performed administrative tasks.</p>

                    <div class="space-y-2.5">
                        <a href="{{ route('sparepart.all') }}"
                            class="w-full py-2.5 px-4 bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-bold text-xs rounded-xl flex items-center justify-center gap-2 transition-all shadow-md">
                            <i class="fa-solid fa-file-excel"></i> Export Global Stock
                        </a>
                        <a href="{{ route('schedule.index') }}"
                            class="w-full py-2.5 px-4 bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs rounded-xl flex items-center justify-center gap-2 transition-all shadow-md">
                            <i class="fa-solid fa-calendar-plus"></i> Manage Schedules
                        </a>
                        <a href="{{ route('attendance.index') }}"
                            class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl flex items-center justify-center gap-2 transition-all shadow-md">
                            <i class="fa-solid fa-clipboard-user"></i> Process Attendance
                        </a>
                        @if (auth()->user()?->role === 'superadmin')
                            <a href="{{ route('employee.index') }}"
                                class="w-full py-2.5 px-4 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-bold text-xs rounded-xl flex items-center justify-center gap-2 transition-all">
                                <i class="fa-solid fa-user-plus"></i> Register New Employee
                            </a>
                        @endif
                    </div>
                </div>

                {{-- SYSTEM ALERTS WIDGET --}}
                <div class="p-6 bg-white border shadow-xs border-slate-200/80 rounded-3xl">
                    <h3 class="mb-4 text-xs font-bold tracking-wider uppercase text-slate-400">System Notifications</h3>

                    <div class="space-y-3">
                        @if (($criticalStock ?? 0) > 0)
                            <div class="flex items-start gap-3 p-3 border bg-rose-50 border-rose-200 rounded-2xl">
                                <i class="fa-solid fa-triangle-exclamation text-rose-500 text-base mt-0.5"></i>
                                <div>
                                    <p class="text-xs font-bold text-rose-900">Inventory Warning</p>
                                    <p class="text-[11px] font-medium text-rose-700 mt-0.5">
                                        {{ $criticalStock }} items require immediate stock replenishment.
                                    </p>
                                </div>
                            </div>
                        @else
                            <div class="flex items-start gap-3 p-3 border bg-emerald-50 border-emerald-200 rounded-2xl">
                                <i class="fa-solid fa-circle-check text-emerald-500 text-base mt-0.5"></i>
                                <div>
                                    <p class="text-xs font-bold text-emerald-900">Stock Levels Optimal</p>
                                    <p class="text-[11px] font-medium text-emerald-700 mt-0.5">
                                        All inventory items are above the safety threshold.
                                    </p>
                                </div>
                            </div>
                        @endif

                        <div class="flex items-start gap-3 p-3 border bg-slate-50 border-slate-200/70 rounded-2xl">
                            <i class="fa-solid fa-clock-rotate-left text-slate-400 text-base mt-0.5"></i>
                            <div>
                                <p class="text-xs font-bold text-slate-800">Shift Rota Updated</p>
                                <p class="text-[11px] font-medium text-slate-500 mt-0.5">
                                    Schedule configuration for {{ now()->format('F Y') }} is active.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>
@endsection
